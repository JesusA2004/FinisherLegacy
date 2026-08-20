<?php

namespace App\Support\Integrations;

final class ProviderConnectionTestResult
{
    /**
     * @param  array<string, mixed>  $providerInfo
     */
    public function __construct(
        public readonly bool $success,
        public readonly int $latencyMs,
        public readonly array $providerInfo = [],
        public readonly ?string $message = null,
    ) {}
}
