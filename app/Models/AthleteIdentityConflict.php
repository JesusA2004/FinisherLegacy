<?php

namespace App\Models;

use App\Enums\AthleteIdentityConflictStatus;
use Database\Factories\AthleteIdentityConflictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "This might be the same person" — created whenever
 * App\Services\Athletes\AthleteIdentityMatcher can't confidently auto-link
 * or confidently rule a match out. See
 * docs/adr/0004-athlete-canonical-identity.md §Identity conflict.
 */
#[Fillable([
    'event_participant_id', 'candidate_athlete_id', 'candidates', 'source_type', 'source_reference',
    'incoming_data', 'confidence', 'reason', 'status', 'resolved_by', 'resolved_at', 'resolution',
])]
class AthleteIdentityConflict extends Model
{
    /** @use HasFactory<AthleteIdentityConflictFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'incoming_data' => 'array',
            'candidates' => 'array',
            'status' => AthleteIdentityConflictStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EventParticipant, $this> */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    /** @return BelongsTo<Athlete, $this> */
    public function candidateAthlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'candidate_athlete_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
