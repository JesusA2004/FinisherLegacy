<?php

namespace App\Enums;

/**
 * Deliberately 3 states, not 15 — see
 * docs/adr/0004-athlete-canonical-identity.md §Identity status.
 */
enum AthleteIdentityStatus: string
{
    case Active = 'active';
    case PossibleDuplicate = 'possible_duplicate';
    case Merged = 'merged';
}
