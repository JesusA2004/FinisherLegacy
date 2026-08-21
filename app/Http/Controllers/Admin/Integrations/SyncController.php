<?php

namespace App\Http\Controllers\Admin\Integrations;

use App\Enums\ExternalSyncType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Integrations\SyncRunResource;
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
            'run' => (new SyncRunResource($syncRun))->toArray(request()),
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
        return response()->json(['data' => (new SyncRunResource($syncRun))->toArray(request())]);
    }
}
