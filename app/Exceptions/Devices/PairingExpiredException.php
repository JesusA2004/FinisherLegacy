<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

class PairingExpiredException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este código de emparejamiento expiró. Genera uno nuevo desde el dispositivo.',
            DeviceErrorCode::PairingExpired,
            410,
        );
    }
}
