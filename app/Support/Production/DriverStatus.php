<?php

namespace App\Support\Production;

/**
 * `code` is a small fixed vocabulary a Desktop UI maps to a message —
 * never a free-text string the UI has to parse (docs/adr/0007 §Driver
 * contract).
 */
final class DriverStatus
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $detail = null,
    ) {}

    public static function ready(): self
    {
        return new self('ready');
    }

    public static function notReady(string $detail): self
    {
        return new self('device_not_ready', $detail);
    }

    public static function interlockOpen(): self
    {
        return new self('interlock_open', 'La compuerta de seguridad está abierta.');
    }
}
