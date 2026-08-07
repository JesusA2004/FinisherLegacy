<?php

namespace App\Models;

use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use Database\Factories\PlateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'user_id', 'medal_id', 'event_edition_id', 'event_participant_id', 'plate_template_id', 'legacy_code_id',
    'serial_number', 'generation_mode', 'athlete_name', 'bib_number', 'event_name', 'race_name',
    'official_time', 'pace', 'event_date', 'status', 'linked_at', 'produced_at', 'delivered_at',
])]
class Plate extends Model
{
    /** @use HasFactory<PlateFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'generation_mode' => PlateGenerationMode::class,
            'status' => PlateStatus::class,
            'event_date' => 'date',
            'linked_at' => 'datetime',
            'produced_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Medal, $this> */
    public function medal(): BelongsTo
    {
        return $this->belongsTo(Medal::class);
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return BelongsTo<EventParticipant, $this> */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    /** @return BelongsTo<PlateTemplate, $this> */
    public function plateTemplate(): BelongsTo
    {
        return $this->belongsTo(PlateTemplate::class);
    }

    /** @return BelongsTo<LegacyCode, $this> */
    public function legacyCode(): BelongsTo
    {
        return $this->belongsTo(LegacyCode::class);
    }

    /** @return HasMany<ProductionJob, $this> */
    public function productionJobs(): HasMany
    {
        return $this->hasMany(ProductionJob::class);
    }

    /** @return HasOne<ProductionJob, $this> */
    public function latestProductionJob(): HasOne
    {
        return $this->hasOne(ProductionJob::class)->latestOfMany();
    }

    /** @return HasMany<PlateReprint, $this> */
    public function reprints(): HasMany
    {
        return $this->hasMany(PlateReprint::class);
    }

    /** @return HasMany<EventIncident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(EventIncident::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
