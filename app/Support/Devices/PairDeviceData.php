<?php

namespace App\Support\Devices;

use Illuminate\Http\Request;

/**
 * Validated input for App\Actions\Devices\RequestDevicePairing. Actions
 * never receive a raw Request — see docs/adr/0002 §Application Actions.
 */
final class PairDeviceData
{
    /**
     * @param  array<string, mixed>|null  $capabilities
     */
    private function __construct(
        public readonly string $name,
        public readonly ?string $stationCode,
        public readonly ?string $appVersion,
        public readonly ?array $capabilities,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'station_code' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:30'],
            'capabilities' => ['nullable', 'array'],
        ]);

        return new self(
            name: $data['name'],
            stationCode: $data['station_code'] ?? null,
            appVersion: $data['app_version'] ?? null,
            capabilities: $data['capabilities'] ?? null,
        );
    }
}
