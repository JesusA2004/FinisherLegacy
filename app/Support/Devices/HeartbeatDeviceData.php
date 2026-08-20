<?php

namespace App\Support\Devices;

use Illuminate\Http\Request;

final class HeartbeatDeviceData
{
    /**
     * @param  array<string, mixed>|null  $capabilities
     */
    private function __construct(
        public readonly ?string $appVersion,
        public readonly ?array $capabilities,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'app_version' => ['nullable', 'string', 'max:30'],
            'capabilities' => ['nullable', 'array'],
        ]);

        return new self(
            appVersion: $data['app_version'] ?? null,
            capabilities: $data['capabilities'] ?? null,
        );
    }
}
