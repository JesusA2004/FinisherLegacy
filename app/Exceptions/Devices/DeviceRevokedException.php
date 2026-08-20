<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

class DeviceRevokedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este dispositivo fue revocado y ya no puede operar.',
            DeviceErrorCode::DeviceRevoked,
            403,
        );
    }
}
