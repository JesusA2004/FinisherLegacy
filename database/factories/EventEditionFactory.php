<?php

namespace Database\Factories;

use App\Enums\EditionStatus;
use App\Enums\OperationMode;
use App\Enums\ResultsStatus;
use App\Models\Event;
use App\Models\EventEdition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventEdition>
 */
class EventEditionFactory extends Factory
{
    protected $model = EventEdition::class;

    public function definition(): array
    {
        $year = (int) date('Y');
        $eventDate = fake()->dateTimeBetween('-2 months', '+6 months');

        return [
            'event_id' => Event::factory(),
            'name' => "Edición {$year}",
            'year' => $year,
            'event_date' => $eventDate,
            'city' => fake()->city(),
            'state' => fake()->randomElement(['CDMX', 'Jalisco', 'Nuevo León', 'Puebla', 'Querétaro', 'Yucatán']),
            'country' => 'México',
            'timezone' => 'America/Mexico_City',
            'registration_open_at' => fake()->dateTimeBetween('-6 months', '-3 months'),
            'registration_close_at' => fake()->dateTimeBetween('-2 months', '-1 week'),
            'operation_mode' => OperationMode::Hybrid,
            'status' => EditionStatus::Published,
            'results_status' => ResultsStatus::Pending,
        ];
    }
}
