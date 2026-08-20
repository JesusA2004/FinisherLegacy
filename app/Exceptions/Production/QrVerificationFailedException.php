<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

/**
 * Never reveals the expected code in the response — see
 * docs/adr/0003-production-state-machine.md §QR.
 */
class QrVerificationFailedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'El código QR escaneado no corresponde a esta placa.',
            DeviceErrorCode::QrVerificationFailed,
            422,
        );
    }
}
