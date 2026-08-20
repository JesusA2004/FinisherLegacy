<?php

namespace App\Http\Controllers\Api\V1\Devices;

use App\Actions\Devices\ConfirmDevicePairing;
use App\Actions\Devices\RequestDevicePairing;
use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Devices\ProductionDeviceResource;
use App\Support\Devices\ConfirmDevicePairingData;
use App\Support\Devices\PairDeviceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unauthenticated by design — a device has no token yet when it's asking
 * for one. Rate-limited (`throttle:api-register`, the same limiter the
 * public register/preregister endpoints share) so this can't be used to
 * spam pairing requests. See docs/adr/0002 §Pairing.
 */
class PairingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly RequestDevicePairing $requestPairing,
        private readonly ConfirmDevicePairing $confirmPairing,
    ) {}

    public function pair(Request $request): JsonResponse
    {
        $result = $this->requestPairing->handle(PairDeviceData::fromRequest($request));

        return $this->respond([
            'code' => $result['pairingRequest']->code,
            'poll_token' => $result['pollToken'],
            'expires_at' => $result['pairingRequest']->expires_at->toIso8601String(),
        ], 'Muestra este código a un Super Admin para vincular la estación.', status: 201);
    }

    public function confirm(Request $request): JsonResponse
    {
        $result = $this->confirmPairing->handle(ConfirmDevicePairingData::fromRequest($request));

        if ($result['status'] === 'pending') {
            return $this->respond(['status' => 'pending'], 'Esperando aprobación de un Super Admin.');
        }

        return $this->respond([
            'status' => 'completed',
            'device' => new ProductionDeviceResource($result['device']),
            'token' => $result['token'],
        ], 'Dispositivo vinculado.');
    }
}
