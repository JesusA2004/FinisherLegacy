<?php

namespace Database\Factories;

use App\Enums\AthleteIdentityConflictStatus;
use App\Models\AthleteIdentityConflict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteIdentityConflict>
 */
class AthleteIdentityConflictFactory extends Factory
{
    protected $model = AthleteIdentityConflict::class;

    public function definition(): array
    {
        return [
            'event_participant_id' => null,
            'candidate_athlete_id' => null,
            'candidates' => [],
            'source_type' => 'import',
            'source_reference' => null,
            'incoming_data' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => null,
                'birth_date' => null,
            ],
            'confidence' => 80,
            'reason' => 'name_and_birthdate',
            'status' => AthleteIdentityConflictStatus::Pending,
        ];
    }
}
