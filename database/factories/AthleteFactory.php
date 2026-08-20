<?php

namespace Database\Factories;

use App\Enums\AthleteIdentityStatus;
use App\Models\Athlete;
use App\Support\Athletes\AthleteIdentityNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Athlete>
 */
class AthleteFactory extends Factory
{
    protected $model = Athlete::class;

    public function definition(): array
    {
        $normalizer = new AthleteIdentityNormalizer;
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $email = fake()->unique()->safeEmail();

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$firstName} {$lastName}",
            'normalized_first_name' => $normalizer->name($firstName),
            'normalized_last_name' => $normalizer->name($lastName),
            'normalized_full_name' => $normalizer->fullName($firstName, $lastName),
            'email' => $email,
            'normalized_email' => $normalizer->email($email),
            'phone' => null,
            'normalized_phone' => null,
            'birth_date' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'gender' => null,
            'country' => 'MX',
            'identity_status' => AthleteIdentityStatus::Active,
        ];
    }

    public function withoutEmail(): static
    {
        return $this->state(['email' => null, 'normalized_email' => null]);
    }
}
