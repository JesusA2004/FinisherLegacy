<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider_connection_id', 'external_event_id', 'event_edition_id',
    'cursor_before', 'cursor_after', 'live_sync_enabled', 'live_sync_interval_seconds', 'metadata',
])]
class ExternalEventMapping extends Model
{
    protected function casts(): array
    {
        return [
            'live_sync_enabled' => 'boolean',
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
}
