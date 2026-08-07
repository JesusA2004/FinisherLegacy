<?php

namespace Database\Factories;

use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Models\Plate;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plate>
 */
class PlateFactory extends Factory
{
    protected $model = Plate::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'medal_id' => null,
            'event_edition_id' => null,
            'event_participant_id' => null,
            'plate_template_id' => null,
            'legacy_code_id' => null,
            'serial_number' => CodeGenerator::generate('PLT', 8),
            'generation_mode' => PlateGenerationMode::Integrated,
            'athlete_name' => fake()->name(),
            'bib_number' => (string) fake()->numberBetween(1, 50000),
            'event_name' => fake()->city().' Marathon',
            'race_name' => fake()->randomElement(['5K', '10K', '21K', '42K']),
            'official_time' => sprintf('%02d:%02d:%02d', fake()->numberBetween(0, 5), fake()->numberBetween(0, 59), fake()->numberBetween(0, 59)),
            'pace' => sprintf('%d:%02d', fake()->numberBetween(4, 7), fake()->numberBetween(0, 59)),
            'event_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => PlateStatus::Draft,
        ];
    }
}
