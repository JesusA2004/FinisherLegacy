<?php

namespace Database\Factories;

use App\Enums\PlateTemplateVersionStatus;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlateTemplateVersion>
 */
class PlateTemplateVersionFactory extends Factory
{
    protected $model = PlateTemplateVersion::class;

    public function definition(): array
    {
        return [
            'plate_template_id' => PlateTemplate::factory(),
            'version' => 1,
            'front_configuration' => ['elements' => []],
            'back_configuration' => ['elements' => []],
            'status' => PlateTemplateVersionStatus::Draft,
            'created_by' => null,
        ];
    }
}
