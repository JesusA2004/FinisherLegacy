<?php

namespace App\Exceptions\Athletes;

use RuntimeException;

/**
 * Both Athletes being merged already have a different linked User —
 * merging would silently steal one account's identity into another's.
 * Blocked unconditionally; needs manual resolution outside
 * App\Actions\Athletes\MergeAthletes. See docs/adr/0004 §Merge rule.
 */
class AthleteMergeUserConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se puede fusionar: ambos atletas tienen cuentas de usuario distintas vinculadas.');
    }

    public function code(): string
    {
        return 'ATHLETE_MERGE_USER_CONFLICT';
    }
}
