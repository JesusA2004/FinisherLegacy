<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Actions\Integrations\CreateEventFromExternalData;
use App\Actions\Integrations\LinkExternalEvent;
use App\Actions\Integrations\TestProviderConnection;
use App\Enums\ProviderConnectionStatus;
use App\Http\Controllers\Controller;
use App\Models\EventEdition;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Services\Integrations\EventProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Thin — validate, call an Action, render/redirect (docs/adr/0005 §Web
 * controllers). Never touches `credentials` in a response payload (§20-21).
 */
class ProviderConnectionController extends Controller
{
    public function index(EventProviderRegistry $registry): Response
    {
        $connections = ProviderConnection::query()
            ->withCount('eventMappings')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ProviderConnection $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'provider_key' => $c->provider_key,
                'status' => $c->status->value,
                'last_tested_at' => $c->last_tested_at?->diffForHumans(),
                'last_successful_sync_at' => $c->last_successful_sync_at?->diffForHumans(),
                'event_mappings_count' => $c->event_mappings_count,
            ]);

        return Inertia::render('admin/integrations/Index', [
            'connections' => $connections,
            'providerKeys' => $registry->keys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider_key' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:1000'],
        ]);

        ProviderConnection::create([
            'uuid' => (string) Str::uuid(),
            'provider_key' => $data['provider_key'],
            'name' => $data['name'],
            'base_url' => $data['base_url'] ?? null,
            'credentials' => $data['api_key'] ?? null,
            'settings' => ['chunk_size' => 250],
            'status' => ProviderConnectionStatus::Untested,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conexión creada.']);

        return back();
    }

    public function test(ProviderConnection $providerConnection, TestProviderConnection $action): RedirectResponse
    {
        $result = $action->handle($providerConnection);

        Inertia::flash('toast', [
            'type' => $result->success ? 'success' : 'error',
            'message' => $result->success
                ? "Conexión OK ({$result->latencyMs}ms)."
                : ($result->message ?? 'La conexión falló.'),
        ]);

        return back();
    }

    public function show(ProviderConnection $providerConnection, EventProviderRegistry $registry): Response
    {
        $adapter = $registry->get($providerConnection->provider_key);

        $availableEvents = [];
        $listError = null;

        try {
            $availableEvents = collect($adapter->listEvents($providerConnection))->map(fn ($e) => [
                'external_id' => $e->externalId,
                'name' => $e->name,
                'date' => $e->date,
                'city' => $e->city,
            ])->all();
        } catch (\Throwable $e) {
            $listError = 'No se pudo listar eventos del proveedor.';
        }

        $mappings = $providerConnection->eventMappings()->with('eventEdition.event')->get()
            ->map(function ($mapping) use ($providerConnection) {
                $lastRun = $providerConnection->syncRuns()
                    ->where('event_edition_id', $mapping->event_edition_id)
                    ->latest('started_at')
                    ->first();

                return [
                    'id' => $mapping->id,
                    'external_event_id' => $mapping->external_event_id,
                    'event' => $mapping->eventEdition?->event?->name,
                    'edition' => $mapping->eventEdition?->name,
                    'event_edition_id' => $mapping->event_edition_id,
                    'last_sync' => $lastRun ? [
                        'id' => $lastRun->id,
                        'status' => $lastRun->status->value,
                        'started_at' => $lastRun->started_at?->diffForHumans(),
                        'participants_received' => $lastRun->participants_received,
                        'results_received' => $lastRun->results_received,
                        'errors_count' => $lastRun->errors_count,
                    ] : null,
                ];
            });

        return Inertia::render('admin/integrations/Show', [
            'connection' => [
                'id' => $providerConnection->id,
                'name' => $providerConnection->name,
                'provider_key' => $providerConnection->provider_key,
                'status' => $providerConnection->status->value,
                'base_url' => $providerConnection->base_url,
                'last_tested_at' => $providerConnection->last_tested_at?->diffForHumans(),
                'last_successful_sync_at' => $providerConnection->last_successful_sync_at?->diffForHumans(),
            ],
            'availableEvents' => $availableEvents,
            'listError' => $listError,
            'mappings' => $mappings,
            'editions' => EventEdition::with('event')->orderByDesc('event_date')->limit(100)->get()
                ->map(fn (EventEdition $edition) => ['id' => $edition->id, 'name' => $edition->event->name.' — '.$edition->name]),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function linkEvent(
        Request $request,
        ProviderConnection $providerConnection,
        LinkExternalEvent $linkEvent,
        CreateEventFromExternalData $createEvent,
        EventProviderRegistry $registry,
    ): RedirectResponse {
        $data = $request->validate([
            'external_event_id' => ['required', 'string'],
            'mode' => ['required', Rule::in(['link', 'create'])],
            'event_edition_id' => ['required_if:mode,link', 'nullable', 'integer', 'exists:event_editions,id'],
            'sport_id' => ['required_if:mode,create', 'nullable', 'integer', 'exists:sports,id'],
        ]);

        DB::transaction(function () use ($data, $providerConnection, $linkEvent, $createEvent, $registry) {
            if ($data['mode'] === 'link') {
                $edition = EventEdition::findOrFail((int) $data['event_edition_id']);
                $linkEvent->handle($providerConnection, $data['external_event_id'], $edition);

                return;
            }

            $adapter = $registry->get($providerConnection->provider_key);
            $eventData = $adapter->fetchEvent($providerConnection, $data['external_event_id']);
            $sport = Sport::findOrFail((int) $data['sport_id']);
            $createEvent->handle($eventData, $providerConnection, $sport);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Evento vinculado.']);

        return back();
    }
}
