<?php

namespace Database\Factories;

use App\Models\PlateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlateTemplate>
 */
class PlateTemplateFactory extends Factory
{
    protected $model = PlateTemplate::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' Plate Template';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => null,
            'width_mm' => 60,
            'height_mm' => 40,
            'material' => 'Acero inoxidable',
            'orientation' => 'landscape',
            'safe_margin_mm' => 3,
            'created_by' => null,
            'active' => true,
        ];
    }
}
