<?php

namespace App\Services\Athletes;

use App\Enums\AthleteMatchReason;
use App\Models\Athlete;
use App\Models\AthleteExternalIdentity;
use App\Support\Athletes\AthleteIdentityCandidateData;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Deterministic identity matching — no ML, no fuzzy scoring beyond the
 * fixed table in App\Enums\AthleteMatchReason. See
 * docs/adr/0004-athlete-canonical-identity.md §Identity matcher for the
 * full reasoning behind every threshold.
 *
 * Tries signals strongest-first and stops at the first tier that produces
 * any candidate — a weaker signal never overrides or combines with a
 * stronger one that already answered. Never merges anything itself; it
 * only classifies (App\Enums\AthleteMatchOutcome) and hands candidates
 * back to the caller (App\Actions\Athletes\ResolveAthleteIdentity), which
 * decides what to do.
 */
class AthleteIdentityMatcher
{
    /**
     * A single candidate at or above this confidence is auto-linked
     * (matches ExternalIdentityExact=100, VerifiedEmailAndBirthdate=100,
     * EmailExact=95, PhoneAndBirthdate=95 — see App\Enums\AthleteMatchReason).
     */
    private const AUTO_LINK_THRESHOLD = 95;

    /**
     * Below this, a match is too weak to even flag for review — matches
     * App\Enums\AthleteMatchReason::NameOnly (30). A pile of "Juan Pérez"
     * name-only collisions must never flood the conflicts queue; each just
     * becomes its own Athlete. Between this and AUTO_LINK_THRESHOLD
     * (NameAndBirthdate=80, or several candidates tied at a stronger tier)
     * is exactly the "not certain, not nothing" band that becomes an
     * App\Models\AthleteIdentityConflict — see docs/adr/0004 §18-20, the
     * absolute rule that name alone never auto-merges.
     */
    private const CONFLICT_THRESHOLD = 60;

    /**
     * @param  bool  $excludeAthletesLinkedToOtherUsers  Registration-only guard (docs/adr/0004
     *                                                    §Registration match): an Athlete already
     *                                                    linked to a different User can never be
     *                                                    silently claimed by a new registrant.
     */
    public function match(AthleteIdentityCandidateData $data, bool $excludeAthletesLinkedToOtherUsers = false): AthleteMatchResult
    {
        if ($data->hasExternalIdentity()) {
            $identity = AthleteExternalIdentity::query()
                ->where('provider', $data->externalProvider)
                ->where('provider_connection_id', $data->externalConnectionId ?? '')
                ->where('external_subject_id', $data->externalSubjectId)
                ->first();

            if ($identity !== null) {
                $athlete = $identity->athlete;

                if (! $excludeAthletesLinkedToOtherUsers || $athlete->user_id === null) {
                    return AthleteMatchResult::matched(collect([
                        new AthleteMatchCandidate($athlete, AthleteMatchReason::ExternalIdentityExact, AthleteMatchReason::ExternalIdentityExact->confidence()),
                    ]));
                }
            }
        }

        if ($data->normalizedEmail !== null && $data->emailVerified && $data->birthDate !== null) {
            $candidates = $this->candidatesFor(
                AthleteMatchReason::VerifiedEmailAndBirthdate,
                fn (Builder $q) => $q->where('normalized_email', $data->normalizedEmail)->whereDate('birth_date', $data->birthDate),
                $excludeAthletesLinkedToOtherUsers,
            );

            if ($candidates->isNotEmpty()) {
                return $this->decide($candidates);
            }
        }

        if ($data->normalizedEmail !== null) {
            $candidates = $this->candidatesFor(
                AthleteMatchReason::EmailExact,
                fn (Builder $q) => $q->where('normalized_email', $data->normalizedEmail),
                $excludeAthletesLinkedToOtherUsers,
            );

            if ($candidates->isNotEmpty()) {
                return $this->decide($candidates);
            }
        }

        if ($data->normalizedPhone !== null && $data->birthDate !== null) {
            $candidates = $this->candidatesFor(
                AthleteMatchReason::PhoneAndBirthdate,
                fn (Builder $q) => $q->where('normalized_phone', $data->normalizedPhone)->whereDate('birth_date', $data->birthDate),
                $excludeAthletesLinkedToOtherUsers,
            );

            if ($candidates->isNotEmpty()) {
                return $this->decide($candidates);
            }
        }

        if ($data->birthDate !== null) {
            $candidates = $this->candidatesFor(
                AthleteMatchReason::NameAndBirthdate,
                fn (Builder $q) => $q->where('normalized_full_name', $data->normalizedFullName)->whereDate('birth_date', $data->birthDate),
                $excludeAthletesLinkedToOtherUsers,
            );

            if ($candidates->isNotEmpty()) {
                return $this->decide($candidates);
            }
        }

        $candidates = $this->candidatesFor(
            AthleteMatchReason::NameOnly,
            fn (Builder $q) => $q->where('normalized_full_name', $data->normalizedFullName),
            $excludeAthletesLinkedToOtherUsers,
        );

        return $this->decide($candidates);
    }

    /**
     * @param  Closure(Builder<Athlete>): Builder<Athlete>  $scope
     * @return Collection<int, AthleteMatchCandidate>
     */
    private function candidatesFor(AthleteMatchReason $reason, Closure $scope, bool $excludeLinkedToOtherUsers): Collection
    {
        $query = Athlete::query()->where('identity_status', '!=', 'merged');
        $query = $scope($query);

        if ($excludeLinkedToOtherUsers) {
            $query->whereNull('user_id');
        }

        return $query->get()->map(fn (Athlete $athlete) => new AthleteMatchCandidate($athlete, $reason, $reason->confidence()))->values();
    }

    /**
     * @param  Collection<int, AthleteMatchCandidate>  $candidates
     */
    private function decide(Collection $candidates): AthleteMatchResult
    {
        if ($candidates->isEmpty()) {
            return AthleteMatchResult::noMatch();
        }

        $confidence = $candidates->first()->confidence;

        if ($confidence < self::CONFLICT_THRESHOLD) {
            return AthleteMatchResult::noMatch();
        }

        if ($candidates->count() === 1 && $confidence >= self::AUTO_LINK_THRESHOLD) {
            return AthleteMatchResult::matched($candidates);
        }

        return AthleteMatchResult::conflict($candidates);
    }
}
