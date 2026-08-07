<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Database\Factories\EventResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'event_participant_id', 'official_time', 'chip_time', 'pace', 'overall_position',
    'gender_position', 'category_position', 'status', 'result_source', 'verified_at',
])]
class EventResult extends Model
{
    /** @use HasFactory<EventResultFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EventParticipant, $this> */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
