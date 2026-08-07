<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->randomElement(['Marathon', 'Trail Run', 'Triatlón', 'Carrera 10K']);

        return [
            'organizer_id' => Organizer::factory(),
            'sport_id' => Sport::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->paragraph(),
            'status' => EventStatus::Published,
        ];
    }
}
