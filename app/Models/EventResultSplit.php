<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_result_id', 'type', 'label', 'sequence', 'distance_value', 'distance_unit',
    'segment_time', 'elapsed_time', 'pace', 'metadata',
])]
class EventResultSplit extends Model
{
    protected function casts(): array
    {
        return [
            'distance_value' => 'decimal:3',
            'sequence' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<EventResult, $this> */
    public function eventResult(): BelongsTo
    {
        return $this->belongsTo(EventResult::class);
    }
}
