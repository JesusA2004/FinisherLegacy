<?php

namespace Database\Factories;

use App\Enums\OrganizerStatus;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    protected $model = Organizer::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'legal_name' => "{$name} S.A. de C.V.",
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'status' => OrganizerStatus::Active,
        ];
    }
}
