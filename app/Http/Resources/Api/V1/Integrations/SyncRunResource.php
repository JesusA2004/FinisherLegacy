<?php

namespace App\Http\Resources\Api\V1\Integrations;

use App\Models\ExternalSyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The one `ExternalSyncRun` presentation — Web
 * (App\Http\Controllers\Admin\Integrations\SyncController) and the API
 * (App\Http\Controllers\Api\V1\Integrations\SyncController) both render
 * this instead of each formatting the same counters
 * (docs/api/use-case-matrix.md). Never exposes `provider_connection.credentials`
 * — only `providerConnection.name` (docs/adr/0005 §Security).
 *
 * @mixin ExternalSyncRun
 */
class SyncRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'sync_type' => $this->sync_type->value,
            'provider' => $this->providerConnection->name,
            'event' => $this->eventEdition?->event?->name,
            'edition' => $this->eventEdition?->name,
            'started_at' => $this->started_at?->diffForHumans(),
            'completed_at' => $this->completed_at?->diffForHumans(),
            'events_received' => $this->events_received,
            'participants_received' => $this->participants_received,
            'participants_created' => $this->participants_created,
            'participants_updated' => $this->participants_updated,
            'results_received' => $this->results_received,
            'results_created' => $this->results_created,
            'results_updated' => $this->results_updated,
            'splits_received' => $this->splits_received,
            'identity_conflicts' => $this->identity_conflicts,
            'errors_count' => $this->errors_count,
        ];
    }
}
