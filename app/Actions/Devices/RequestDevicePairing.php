<?php

namespace App\Actions\Devices;

use App\Models\DevicePairingRequest;
use App\Support\CodeGenerator;
use App\Support\Devices\PairDeviceData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Step 1 of pairing: an unregistered desktop asks for a code. Returns the
 * plaintext poll token to the caller exactly once — it is never persisted,
 * only its SHA-256 hash is (mirrors how Sanctum itself never stores a
 * plaintext token). See docs/adr/0002 §Pairing.
 */
class RequestDevicePairing
{
    /**
     * @return array{pairingRequest: DevicePairingRequest, pollToken: string}
     */
    public function handle(PairDeviceData $data): array
    {
        $pollToken = Str::random(64);

        $pairingRequest = DevicePairingRequest::create([
            'code' => CodeGenerator::unique(
                '',
                fn (string $code) => DevicePairingRequest::where('code', $code)->exists(),
                (int) config('finisher.pairing_code_length', 6),
            ),
            'poll_token_hash' => hash('sha256', $pollToken),
            'requested_name' => $data->name,
            'requested_station_code' => $data->stationCode,
            'requested_app_version' => $data->appVersion,
            'requested_capabilities' => $data->capabilities,
            'expires_at' => now()->addMinutes((int) config('finisher.pairing_expiration_minutes', 10)),
        ]);

        Log::info('production_device.pairing_requested', [
            'pairing_request_id' => $pairingRequest->id,
            'code' => $pairingRequest->code,
            'name' => $data->name,
        ]);

        return ['pairingRequest' => $pairingRequest, 'pollToken' => $pollToken];
    }
}
