<?php

namespace App\Models;

use App\Enums\ExternalSyncStatus;
use App\Enums\ExternalSyncType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bulk observability for one sync attempt — never one ActivityLog row per
 * participant (docs/adr/0005 §85, would be millions of rows for a 50k
 * roster). The Admin "sync dashboard" reads straight off these counters.
 */
#[Fillable([
    'uuid', 'provider_connection_id', 'event_edition_id', 'sync_type', 'status',
    'started_at', 'completed_at',
    'events_received', 'participants_received', 'participants_created', 'participants_updated',
    'results_received', 'results_created', 'results_updated', 'splits_received',
    'identity_conflicts', 'errors_count', 'cursor_before', 'cursor_after', 'metadata',
])]
class ExternalSyncRun extends Model
{
    protected function casts(): array
    {
        return [
            'sync_type' => ExternalSyncType::class,
            'status' => ExternalSyncStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<ProviderConnection, $this> */
    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return HasMany<ExternalSyncError, $this> */
    public function errors(): HasMany
    {
        return $this->hasMany(ExternalSyncError::class, 'sync_run_id');
    }
}
