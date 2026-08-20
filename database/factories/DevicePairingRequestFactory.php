<?php

namespace Database\Factories;

use App\Enums\DevicePairingStatus;
use App\Models\DevicePairingRequest;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevicePairingRequest>
 */
class DevicePairingRequestFactory extends Factory
{
    protected $model = DevicePairingRequest::class;

    public function definition(): array
    {
        return [
            'code' => CodeGenerator::unique('', fn (string $c) => DevicePairingRequest::where('code', $c)->exists(), 6),
            'poll_token_hash' => hash('sha256', fake()->uuid()),
            'status' => DevicePairingStatus::Pending,
            'requested_name' => 'Estación de prueba',
            'requested_station_code' => null,
            'requested_app_version' => '1.0.0-mock',
            'requested_capabilities' => null,
            'production_device_id' => null,
            'machine_profile_id' => null,
            'event_edition_id' => null,
            'approved_by' => null,
            'approved_at' => null,
            'completed_at' => null,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => DevicePairingStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => DevicePairingStatus::Completed,
            'approved_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
