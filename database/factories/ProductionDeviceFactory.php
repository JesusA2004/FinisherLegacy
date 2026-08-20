<?php

namespace Database\Factories;

use App\Enums\ProductionDeviceStatus;
use App\Models\ProductionDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductionDevice>
 */
class ProductionDeviceFactory extends Factory
{
    protected $model = ProductionDevice::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'Estación '.fake()->numberBetween(1, 20),
            'station_code' => null,
            'machine_profile_id' => null,
            'event_edition_id' => null,
            'status' => ProductionDeviceStatus::Active,
            // A factory-made device defaults to "recently seen" (online) —
            // Slice 2 requires a device to be online before it can claim a
            // job (docs/adr/0003 §81). Tests exercising the offline path
            // override this explicitly (`->create(['last_seen_at' => null])`).
            'last_seen_at' => now(),
            'app_version' => '1.0.0-mock',
            'capabilities' => [
                'laser_type' => 'fiber',
                'driver' => 'mock',
                'supports_vector' => true,
                'supports_auto_focus' => false,
                'work_area_mm' => ['width' => 200, 'height' => 200],
            ],
            'metadata' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(['status' => ProductionDeviceStatus::Revoked]);
    }

    public function pending(): static
    {
        return $this->state(['status' => ProductionDeviceStatus::Pending]);
    }
}
