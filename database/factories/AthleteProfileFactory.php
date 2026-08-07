<?php

namespace Database\Factories;

use App\Enums\ProfileVisibility;
use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteProfile>
 */
class AthleteProfileFactory extends Factory
{
    protected $model = AthleteProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->userName(),
            'bio' => fake()->optional()->sentence(12),
            'birth_date' => fake()->optional()->date(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['CDMX', 'Jalisco', 'Nuevo León', 'Puebla', 'Querétaro', 'Yucatán']),
            'country' => 'México',
            'main_sport_id' => null,
            'profile_visibility' => ProfileVisibility::Public,
        ];
    }
}
