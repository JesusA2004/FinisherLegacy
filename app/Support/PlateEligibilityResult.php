<?php

namespace App\Support;

final class PlateEligibilityResult
{
    /**
     * @param  list<string>  $reasons  'NO_RESULT'|'NO_TEMPLATE'|'IDENTITY_CONFLICT'|'PLATE_ALREADY_EXISTS'
     */
    public function __construct(
        public readonly bool $eligible,
        public readonly array $reasons,
    ) {}
}
