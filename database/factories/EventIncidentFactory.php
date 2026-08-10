<?php

namespace Database\Factories;

use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use App\Models\EventEdition;
use App\Models\EventIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventIncident>
 */
class EventIncidentFactory extends Factory
{
    protected $model = EventIncident::class;

    public function definition(): array
    {
        return [
            'event_edition_id' => EventEdition::factory(),
            'event_participant_id' => null,
            'plate_id' => null,
            'reported_by' => User::factory(),
            'type' => fake()->randomElement(IncidentType::cases())->value,
            'description' => fake()->sentence(),
            'status' => IncidentStatus::Open,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
