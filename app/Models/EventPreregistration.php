<?php

namespace App\Models;

use App\Enums\PreregistrationStatus;
use Database\Factories\EventPreregistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_edition_id', 'event_race_id', 'user_id', 'first_name', 'last_name', 'email',
    'phone', 'bib_number', 'token', 'qr_token', 'status', 'matched_participant_id', 'claimed_at',
])]
class EventPreregistration extends Model
{
    /** @use HasFactory<EventPreregistrationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PreregistrationStatus::class,
            'claimed_at' => 'datetime',
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

    /** @return BelongsTo<EventParticipant, $this> */
    public function matchedParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class, 'matched_participant_id');
    }
}
