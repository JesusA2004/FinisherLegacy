<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Database\Factories\EventResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'event_participant_id', 'official_time', 'chip_time', 'pace', 'overall_position',
    'gender_position', 'category_position', 'status', 'result_source', 'verified_at',
    'manual_override_at', 'manual_override_by', 'manual_override_fields',
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
            'manual_override_at' => 'datetime',
            'manual_override_fields' => 'array',
        ];
    }

    /** @return BelongsTo<EventParticipant, $this> */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function manualOverrideByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_override_by');
    }

    /**
     * A locked field never gets overwritten by the next provider sync
     * (docs/adr/0005-unified-event-ingestion.md §97-99) — an admin
     * correcting `official_time` doesn't lock `pace` too.
     */
    public function hasLockedField(string $field): bool
    {
        return in_array($field, $this->manual_override_fields ?? [], true);
    }

    /** @return HasMany<EventResultSplit, $this> */
    public function splits(): HasMany
    {
        return $this->hasMany(EventResultSplit::class)->orderBy('sequence');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
