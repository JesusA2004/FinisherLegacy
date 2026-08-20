<?php

namespace App\Actions\Athletes;

use App\Enums\AthleteIdentityConflictStatus;
use App\Enums\AthleteMatchOutcome;
use App\Models\AthleteIdentityConflict;
use App\Models\EventParticipant;
use App\Services\Athletes\AthleteIdentityMatcher;
use App\Services\Athletes\AthleteMatchResult;
use App\Support\Athletes\AthleteIdentityCandidateData;
use App\Support\Athletes\ResolveAthleteIdentityResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The single identity-resolution entry point — import rows, manual
 * participant creation, preregistration conversion, and User registration
 * all call this, never their own matching logic (docs/adr/0004 §75, §112).
 */
class ResolveAthleteIdentity
{
    public function __construct(
        private readonly AthleteIdentityMatcher $matcher,
        private readonly CreateAthlete $createAthlete,
    ) {}

    /**
     * @param  string  $sourceType  'import'|'manual'|'preregistration'|'registration'|'claim'|'backfill'
     */
    public function handle(
        AthleteIdentityCandidateData $data,
        string $sourceType,
        ?string $sourceReference = null,
        ?EventParticipant $participant = null,
        bool $excludeAthletesLinkedToOtherUsers = false,
    ): ResolveAthleteIdentityResult {
        // A per-email lock closes the window where two concurrent imports
        // resolving the same new person could each decide "no match" and
        // create two Athletes for one email — see docs/adr/0004 §108-109.
        // Not used for name-only lookups: those never auto-merge anyway, so
        // there is nothing unsafe about two concurrent "create new" calls.
        if ($data->normalizedEmail !== null) {
            return Cache::lock("athlete-identity:{$data->normalizedEmail}", 10)->block(
                5,
                fn () => $this->resolve($data, $sourceType, $sourceReference, $participant, $excludeAthletesLinkedToOtherUsers),
            );
        }

        return $this->resolve($data, $sourceType, $sourceReference, $participant, $excludeAthletesLinkedToOtherUsers);
    }

    private function resolve(
        AthleteIdentityCandidateData $data,
        string $sourceType,
        ?string $sourceReference,
        ?EventParticipant $participant,
        bool $excludeAthletesLinkedToOtherUsers,
    ): ResolveAthleteIdentityResult {
        return DB::transaction(function () use ($data, $sourceType, $sourceReference, $participant, $excludeAthletesLinkedToOtherUsers) {
            $result = $this->matcher->match($data, $excludeAthletesLinkedToOtherUsers);
            $best = $result->best();

            return match ($result->outcome) {
                AthleteMatchOutcome::Matched => ResolveAthleteIdentityResult::matched(
                    $best->athlete,
                    $best->confidence,
                    $best->reason->value,
                ),
                AthleteMatchOutcome::NoMatch => ResolveAthleteIdentityResult::created(
                    $this->createAthlete->handle($data),
                ),
                AthleteMatchOutcome::Conflict => ResolveAthleteIdentityResult::conflict(
                    $this->recordConflict($data, $result, $sourceType, $sourceReference, $participant),
                    $best?->confidence,
                    $best?->reason->value ?? 'ambiguous_name',
                ),
            };
        });
    }

    private function recordConflict(
        AthleteIdentityCandidateData $data,
        AthleteMatchResult $result,
        string $sourceType,
        ?string $sourceReference,
        ?EventParticipant $participant,
    ): AthleteIdentityConflict {
        $best = $result->best();

        return AthleteIdentityConflict::create([
            'event_participant_id' => $participant?->id,
            'candidate_athlete_id' => $best?->athlete->id,
            'candidates' => $result->candidates
                ->map(fn ($c) => ['athlete_id' => $c->athlete->id, 'confidence' => $c->confidence, 'reason' => $c->reason->value])
                ->all(),
            'source_type' => $sourceType,
            'source_reference' => $sourceReference,
            'incoming_data' => $data->toArray(),
            'confidence' => $best?->confidence,
            'reason' => $best !== null && $result->candidates->count() > 1 ? 'ambiguous_name' : ($best?->reason->value ?? 'ambiguous_name'),
            'status' => AthleteIdentityConflictStatus::Pending,
        ]);
    }
}
