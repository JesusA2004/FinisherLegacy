<?php

namespace Database\Factories;

use App\Enums\ProductionJobStatus;
use App\Models\Plate;
use App\Models\ProductionJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionJob>
 */
class ProductionJobFactory extends Factory
{
    protected $model = ProductionJob::class;

    public function definition(): array
    {
        return [
            'plate_id' => Plate::factory(),
            'event_edition_id' => null,
            'priority' => 0,
            'status' => ProductionJobStatus::Queued,
            'assigned_user_id' => null,
            'queued_at' => now(),
            'attempts' => 0,
        ];
    }
}
