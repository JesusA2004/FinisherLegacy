<?php

namespace App\Support\Production;

final class EngraveResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(): self
    {
        return new self(true);
    }

    public static function failed(string $errorCode, string $errorMessage): self
    {
        return new self(false, $errorCode, $errorMessage);
    }
}
