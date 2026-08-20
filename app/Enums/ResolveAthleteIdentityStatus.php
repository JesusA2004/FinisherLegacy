<?php

namespace App\Enums;

/**
 * The outcome shape of App\Actions\Athletes\ResolveAthleteIdentity — see
 * App\Support\Athletes\ResolveAthleteIdentityResult.
 */
enum ResolveAthleteIdentityStatus: string
{
    case Matched = 'matched';
    case Created = 'created';
    case Conflict = 'conflict';
}
