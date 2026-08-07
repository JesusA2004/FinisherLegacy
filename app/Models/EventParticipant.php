<?php

namespace App\Models;

use App\Enums\ParticipantSource;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationStatus;
use Database\Factories\EventParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'event_edition_id', 'event_race_id', 'user_id', 'external_participant_id', 'bib_number',
    'first_name', 'last_name', 'full_name', 'email', 'phone', 'gender', 'birth_date', 'category',
    'registration_status', 'source', 'source_reference', 'verification_status',
])]
class EventParticipant extends Model
{
    /** @use HasFactory<EventParticipantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'registration_status' => RegistrationStatus::class,
            'source' => ParticipantSource::class,
            'verification_status' => VerificationStatus::class,
        ];
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<EventResult, $this> */
    public function result(): HasOne
    {
        return $this->hasOne(EventResult::class);
    }

    /** @return HasOne<EventPreregistration, $this> */
    public function preregistration(): HasOne
    {
        return $this->hasOne(EventPreregistration::class, 'matched_participant_id');
    }

    /** @return HasMany<Medal, $this> */
    public function medals(): HasMany
    {
        return $this->hasMany(Medal::class);
    }

    /** @return HasMany<Plate, $this> */
    public function plates(): HasMany
    {
        return $this->hasMany(Plate::class);
    }

    /** @return HasMany<EventIncident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(EventIncident::class);
    }
}
