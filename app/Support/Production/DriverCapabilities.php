<?php

namespace App\Support\Production;

final class DriverCapabilities
{
    public function __construct(
        public readonly bool $supportsFraming = false,
        public readonly bool $supportsPauseResume = false,
    ) {}
}
