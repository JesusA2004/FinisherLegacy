<?php

namespace App\Enums;

/**
 * The matcher's own verdict — distinct from
 * App\Enums\ResolveAthleteIdentityStatus, which is the Action's outcome
 * (Created also exists there; the matcher itself never creates anything).
 */
enum AthleteMatchOutcome: string
{
    case Matched = 'matched';
    case Conflict = 'conflict';
    case NoMatch = 'no_match';
}
