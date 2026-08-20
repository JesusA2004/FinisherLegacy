<?php

namespace App\Services\Athletes;

use App\Enums\AthleteMatchReason;
use App\Models\Athlete;

/**
 * One scored candidate from App\Services\Athletes\AthleteIdentityMatcher.
 */
final class AthleteMatchCandidate
{
    public function __construct(
        public readonly Athlete $athlete,
        public readonly AthleteMatchReason $reason,
        public readonly int $confidence,
    ) {}
}
