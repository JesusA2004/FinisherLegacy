<?php

namespace Database\Factories;

use App\Enums\PreregistrationStatus;
use App\Models\EventEdition;
use App\Models\EventPreregistration;
use App\Models\EventRace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventPreregistration>
 */
class EventPreregistrationFactory extends Factory
{
    protected $model = EventPreregistration::class;

    public function definition(): array
    {
        return [
            'event_edition_id' => EventEdition::factory(),
            'event_race_id' => EventRace::factory(),
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'bib_number' => null,
            'token' => Str::random(32),
            'qr_token' => Str::random(24),
            'status' => PreregistrationStatus::Pending,
            'matched_participant_id' => null,
            'claimed_at' => null,
        ];
    }
}
