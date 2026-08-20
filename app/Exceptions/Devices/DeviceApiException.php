<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;
use RuntimeException;

/**
 * Base for every domain exception the Device API needs to surface as
 * `{"error": {"code", "message", "details"}}` (docs/device-api/v1.md).
 * App\Support\Devices\DeviceExceptionRenderer catches this type first,
 * before falling back to generic Laravel exception mapping — see
 * bootstrap/app.php.
 */
abstract class DeviceApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        string $message,
        private readonly DeviceErrorCode $errorCode,
        private readonly int $status,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function deviceErrorCode(): DeviceErrorCode
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
