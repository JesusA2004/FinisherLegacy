<?php

namespace Database\Factories;

use App\Enums\MedalStatus;
use App\Enums\MedalVisibility;
use App\Models\Medal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medal>
 */
class MedalFactory extends Factory
{
    protected $model = Medal::class;

    public function definition(): array
    {
        $eventName = fake()->city().' '.fake()->randomElement(['Marathon', 'Trail Run', '10K']);

        return [
            'user_id' => User::factory(),
            'event_id' => null,
            'event_edition_id' => null,
            'event_race_id' => null,
            'event_participant_id' => null,
            'title' => $eventName,
            'event_name_manual' => $eventName,
            'event_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'distance_label' => fake()->randomElement(['5K', '10K', '21K', '42K']),
            'official_time' => sprintf('%02d:%02d:%02d', fake()->numberBetween(0, 5), fake()->numberBetween(0, 59), fake()->numberBetween(0, 59)),
            'pace' => sprintf('%d:%02d', fake()->numberBetween(4, 7), fake()->numberBetween(0, 59)),
            'city' => fake()->city(),
            'country' => 'México',
            'story' => fake()->optional()->paragraph(),
            'visibility' => MedalVisibility::Public,
            'status' => MedalStatus::Active,
        ];
    }
}
