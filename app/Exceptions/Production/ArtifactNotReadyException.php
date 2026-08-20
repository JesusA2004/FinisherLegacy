<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

class ArtifactNotReadyException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'El archivo de producción de este trabajo todavía no se ha generado.',
            DeviceErrorCode::ArtifactNotReady,
            409,
        );
    }
}
