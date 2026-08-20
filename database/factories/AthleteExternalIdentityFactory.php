<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\AthleteExternalIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteExternalIdentity>
 */
class AthleteExternalIdentityFactory extends Factory
{
    protected $model = AthleteExternalIdentity::class;

    public function definition(): array
    {
        return [
            'athlete_id' => Athlete::factory(),
            'provider' => 'manual',
            'provider_connection_id' => '',
            'external_subject_id' => (string) fake()->unique()->numerify('EXT-#####'),
            'metadata' => null,
        ];
    }
}
