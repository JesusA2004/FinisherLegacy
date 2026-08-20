<?php

namespace App\Support\Production;

final class ReadinessCheck
{
    /**
     * @param  array<string, bool>  $checks
     * @param  list<string>  $blockingReasons
     */
    public function __construct(
        public readonly bool $ready,
        public readonly array $checks,
        public readonly array $blockingReasons,
    ) {}
}
