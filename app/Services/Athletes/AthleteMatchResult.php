<?php

namespace App\Services\Athletes;

use App\Enums\AthleteMatchOutcome;
use Illuminate\Support\Collection;

final class AthleteMatchResult
{
    /**
     * @param  Collection<int, AthleteMatchCandidate>  $candidates  All candidates at the winning tier —
     *                                                              more than one only for AthleteMatchOutcome::Conflict.
     */
    private function __construct(
        public readonly AthleteMatchOutcome $outcome,
        public readonly Collection $candidates,
    ) {}

    /**
     * @param  Collection<int, AthleteMatchCandidate>  $candidates
     */
    public static function matched(Collection $candidates): self
    {
        return new self(AthleteMatchOutcome::Matched, $candidates);
    }

    /**
     * @param  Collection<int, AthleteMatchCandidate>  $candidates
     */
    public static function conflict(Collection $candidates): self
    {
        return new self(AthleteMatchOutcome::Conflict, $candidates);
    }

    public static function noMatch(): self
    {
        return new self(AthleteMatchOutcome::NoMatch, collect());
    }

    public function best(): ?AthleteMatchCandidate
    {
        return $this->candidates->first();
    }
}
