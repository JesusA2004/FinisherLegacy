<?php

namespace Database\Factories;

use App\Enums\MedalImageType;
use App\Models\Medal;
use App\Models\MedalImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedalImage>
 */
class MedalImageFactory extends Factory
{
    protected $model = MedalImage::class;

    public function definition(): array
    {
        $basename = (string) Str::uuid();

        return [
            'medal_id' => Medal::factory(),
            'type' => MedalImageType::Front,
            'original_path' => "medals/1/original/{$basename}.jpg",
            'optimized_path' => "medals/1/display/{$basename}.jpg",
            'thumbnail_path' => "medals/1/thumbnails/{$basename}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(50_000, 500_000),
            'width' => 1600,
            'height' => 1600,
            'sort_order' => 0,
        ];
    }
}
