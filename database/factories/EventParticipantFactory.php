<?php

namespace Database\Factories;

use App\Enums\ParticipantSource;
use App\Enums\RegistrationStatus;
use App\Enums\VerificationStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    protected $model = EventParticipant::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'event_edition_id' => EventEdition::factory(),
            'event_race_id' => EventRace::factory(),
            'user_id' => null,
            'external_participant_id' => null,
            'bib_number' => (string) fake()->unique()->numberBetween(1, 50000),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$firstName} {$lastName}",
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'gender' => fake()->randomElement(['M', 'F']),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-16 years'),
            'category' => fake()->randomElement(['18-29', '30-39', '40-49', '50-59', '60+']),
            'registration_status' => RegistrationStatus::Registered,
            'source' => ParticipantSource::Manual,
            'source_reference' => null,
            'verification_status' => VerificationStatus::Unverified,
        ];
    }
}
