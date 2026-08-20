<?php

namespace App\Exceptions\Devices;

use App\Enums\DeviceErrorCode;

/**
 * The token for this pairing request was already delivered once — by
 * design (§5 of the Slice 1 spec: "el token completo se muestra UNA SOLA
 * VEZ"), we never re-deliver it, so a second confirm is always rejected
 * even if it's the same legitimate desktop that lost the first response.
 */
class PairingAlreadyCompletedException extends DeviceApiException
{
    public function __construct()
    {
        parent::__construct(
            'Este emparejamiento ya se completó — el token ya fue entregado y no se reenvía.',
            DeviceErrorCode::PairingAlreadyCompleted,
            410,
        );
    }
}
