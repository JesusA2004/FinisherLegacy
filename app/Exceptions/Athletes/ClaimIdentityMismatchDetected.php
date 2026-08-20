<?php

namespace App\Exceptions\Athletes;

use RuntimeException;

/**
 * Internal signal only — never meant to reach a controller. Thrown inside
 * App\Services\ClaimLegacyCodeService's claim transaction to unwind it
 * (nothing about the claim should persist), then caught just outside that
 * transaction so the AthleteIdentityConflict record can be created in a
 * fresh, separate statement — creating it INSIDE the transaction that then
 * throws would have the rollback erase the very audit record it just wrote.
 */
class ClaimIdentityMismatchDetected extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $conflictData
     */
    public function __construct(public readonly array $conflictData)
    {
        parent::__construct('Claim identity mismatch — see conflictData.');
    }
}
