<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['provider_connection_id', 'external_event_id', 'external_race_id', 'event_race_id'])]
class ExternalRaceMapping extends Model
{
    /** @return BelongsTo<ProviderConnection, $this> */
    public function providerConnection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class);
    }

    /** @return BelongsTo<EventRace, $this> */
    public function eventRace(): BelongsTo
    {
        return $this->belongsTo(EventRace::class);
    }
}
