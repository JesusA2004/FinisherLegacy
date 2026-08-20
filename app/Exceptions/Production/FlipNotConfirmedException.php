<?php

namespace App\Exceptions\Production;

use App\Enums\DeviceErrorCode;
use App\Exceptions\Devices\DeviceApiException;

/**
 * Never assume "front finished" implies "the plate got flipped" — see
 * docs/adr/0003-production-state-machine.md §Flip.
 */
class FlipNotConfirmedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Confirma que la placa fue volteada antes de grabar el reverso.',
            DeviceErrorCode::FlipNotConfirmed,
            409,
        );
    }
}
