<?php

namespace Database\Factories;

use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionArtifact;
use App\Models\ProductionJob;
use App\Support\PlateRendererVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionArtifact>
 */
class ProductionArtifactFactory extends Factory
{
    protected $model = ProductionArtifact::class;

    public function definition(): array
    {
        return [
            'production_job_id' => ProductionJob::factory(),
            'plate_id' => Plate::factory(),
            'plate_template_version_id' => PlateTemplateVersion::factory(),
            'renderer_version' => PlateRendererVersion::CURRENT,
            'format' => 'svg',
            'front_storage_path' => 'production/artifacts/test/front.svg',
            'front_sha256' => hash('sha256', fake()->uuid()),
            'back_storage_path' => 'production/artifacts/test/back.svg',
            'back_sha256' => hash('sha256', fake()->uuid()),
            'width_mm' => 60,
            'height_mm' => 40,
            'back_transform' => 'none',
            'generated_at' => now(),
        ];
    }
}
