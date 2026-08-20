<?php

namespace App\Models;

use App\Enums\MedalStatus;
use App\Enums\MedalVisibility;
use Database\Factories\MedalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'athlete_id', 'event_id', 'event_edition_id', 'event_race_id', 'event_participant_id', 'title',
    'event_name_manual', 'event_date', 'distance_label', 'official_time', 'pace', 'city', 'country',
    'story', 'visibility', 'status',
])]
class Medal extends Model
{
    /** @use HasFactory<MedalFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Medal $medal) {
            $medal->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'visibility' => MedalVisibility::class,
            'status' => MedalStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * For historical queries independent of `user_id` — see
     * docs/adr/0004-athlete-canonical-identity.md §Medal.
     *
     * @return BelongsTo<Athlete, $this>
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<EventEdition, $this> */
    public function eventEdition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class);
    }

    /** @return BelongsTo<EventRace, $this> */
    public function eventRace(): BelongsTo
    {
        return $this->belongsTo(EventRace::class);
    }

    /** @return BelongsTo<EventParticipant, $this> */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    /** @return HasMany<MedalImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(MedalImage::class);
    }

    /** @return HasMany<Plate, $this> */
    public function plates(): HasMany
    {
        return $this->hasMany(Plate::class);
    }

    /** @return HasMany<LegacyCode, $this> */
    public function legacyCodes(): HasMany
    {
        return $this->hasMany(LegacyCode::class);
    }
}
