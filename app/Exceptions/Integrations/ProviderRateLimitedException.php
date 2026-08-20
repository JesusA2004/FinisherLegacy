<?php

namespace App\Exceptions\Integrations;

use RuntimeException;

class ProviderRateLimitedException extends RuntimeException
{
    public function __construct(public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct('El proveedor respondió 429 (rate limited).');
    }
}
