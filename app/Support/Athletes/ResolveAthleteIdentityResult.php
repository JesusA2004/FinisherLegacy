<?php

namespace App\Support\Athletes;

use App\Enums\ResolveAthleteIdentityStatus;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;

/**
 * What App\Actions\Athletes\ResolveAthleteIdentity returns — a typed
 * result, never a magic array. Exactly one of `athlete`/`conflict` is set,
 * matching `status` (docs/adr/0004 §Create/match pipeline).
 */
final class ResolveAthleteIdentityResult
{
    private function __construct(
        public readonly ResolveAthleteIdentityStatus $status,
        public readonly ?Athlete $athlete,
        public readonly ?int $confidence,
        public readonly ?string $reason,
        public readonly ?AthleteIdentityConflict $conflict,
    ) {}

    public static function matched(Athlete $athlete, int $confidence, string $reason): self
    {
        return new self(ResolveAthleteIdentityStatus::Matched, $athlete, $confidence, $reason, null);
    }

    public static function created(Athlete $athlete): self
    {
        return new self(ResolveAthleteIdentityStatus::Created, $athlete, null, null, null);
    }

    public static function conflict(AthleteIdentityConflict $conflict, ?int $confidence, string $reason): self
    {
        return new self(ResolveAthleteIdentityStatus::Conflict, null, $confidence, $reason, $conflict);
    }
}
