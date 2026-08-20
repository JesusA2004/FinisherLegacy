<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

class PairingRequestNotFoundException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'No existe una solicitud de emparejamiento con ese token.',
            DeviceErrorCode::PairingRequestNotFound,
            404,
        );
    }
}
