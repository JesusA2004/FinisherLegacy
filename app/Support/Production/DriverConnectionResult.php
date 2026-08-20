<?php

namespace App\Support\Production;

final class DriverConnectionResult
{
    public function __construct(
        public readonly bool $connected,
        public readonly ?string $error = null,
    ) {}
}
