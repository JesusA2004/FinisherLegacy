<?php

namespace Database\Factories;

use App\Models\EventEdition;
use App\Models\EventRace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRace>
 */
class EventRaceFactory extends Factory
{
    protected $model = EventRace::class;

    public function definition(): array
    {
        [$name, $distance] = fake()->randomElement([
            ['5K', 5],
            ['10K', 10],
            ['21K', 21.097],
            ['42K', 42.195],
        ]);

        return [
            'event_edition_id' => EventEdition::factory(),
            'name' => $name,
            'distance_value' => $distance,
            'distance_unit' => 'km',
            'race_type' => 'individual',
            'start_time' => '07:00:00',
            'active' => true,
        ];
    }
}
