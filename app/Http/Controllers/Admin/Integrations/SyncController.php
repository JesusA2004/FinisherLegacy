<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\ExternalSyncType;
use App\Http\Controllers\Controller;
use App\Jobs\SyncExternalEventJob;
use App\Models\ExternalEventMapping;
use App\Models\ExternalSyncRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Sincronizar ahora" only ever dispatches the queued job — never calls
 * the provider adapter inline from a web request (docs/adr/0005 §39, §88).
 */
class SyncController extends Controller
{
    public function store(Request $request, ExternalEventMapping $eventMapping): RedirectResponse
    {
        $data = $request->validate([
            'sync_type' => ['nullable', Rule::in(['roster', 'results', 'full'])],
            'full' => ['nullable', 'boolean'],
        ]);

        SyncExternalEventJob::dispatch(
            $eventMapping->id,
            ExternalSyncType::from($data['sync_type'] ?? 'full'),
            (bool) ($data['full'] ?? false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sincronización en cola.']);

        return Redirect::back();
    }

    public function show(ExternalSyncRun $syncRun): Response
    {
        $syncRun->load(['providerConnection', 'eventEdition.event', 'errors' => fn ($q) => $q->orderByDesc('created_at')->limit(100)]);

        return Inertia::render('admin/integrations/SyncRun', [
            'run' => $this->present($syncRun),
            'errors' => $syncRun->errors->map(fn ($e) => [
                'entity_type' => $e->entity_type,
                'external_id' => $e->external_id,
                'code' => $e->code,
                'message' => $e->message,
                'created_at' => $e->created_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Polled every few seconds from the sync run page — a small JSON
     * payload, never a full Inertia reload (docs/adr/0005 §41, §36).
     */
    public function status(ExternalSyncRun $syncRun): JsonResponse
    {
        return response()->json(['data' => $this->present($syncRun)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ExternalSyncRun $syncRun): array
    {
        return [
            'id' => $syncRun->id,
            'status' => $syncRun->status->value,
            'sync_type' => $syncRun->sync_type->value,
            'provider' => $syncRun->providerConnection->name,
            'event' => $syncRun->eventEdition?->event?->name,
            'edition' => $syncRun->eventEdition?->name,
            'started_at' => $syncRun->started_at?->diffForHumans(),
            'completed_at' => $syncRun->completed_at?->diffForHumans(),
            'events_received' => $syncRun->events_received,
            'participants_received' => $syncRun->participants_received,
            'participants_created' => $syncRun->participants_created,
            'participants_updated' => $syncRun->participants_updated,
            'results_received' => $syncRun->results_received,
            'results_created' => $syncRun->results_created,
            'results_updated' => $syncRun->results_updated,
            'splits_received' => $syncRun->splits_received,
            'identity_conflicts' => $syncRun->identity_conflicts,
            'errors_count' => $syncRun->errors_count,
        ];
    }
}
