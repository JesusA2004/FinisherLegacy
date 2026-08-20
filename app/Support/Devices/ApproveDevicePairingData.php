<?php

namespace App\Support\Devices;

use Illuminate\Http\Request;

/**
 * What a Super Admin chooses on the "Estaciones" pending-pairing screen —
 * the physical machine/event mapping only a human can know.
 */
final class ApproveDevicePairingData
{
    private function __construct(
        public readonly ?int $machineProfileId,
        public readonly ?int $eventEditionId,
        public readonly ?string $nameOverride,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'machine_profile_id' => ['nullable', 'integer', 'exists:machine_profiles,id'],
            'event_edition_id' => ['nullable', 'integer', 'exists:event_editions,id'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        return new self(
            machineProfileId: $data['machine_profile_id'] ?? null,
            eventEditionId: $data['event_edition_id'] ?? null,
            nameOverride: $data['name'] ?? null,
        );
    }
}
