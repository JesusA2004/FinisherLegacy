<?php

namespace Database\Factories;

use App\Enums\LegacyCodeStatus;
use App\Models\LegacyCode;
use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LegacyCode>
 */
class LegacyCodeFactory extends Factory
{
    protected $model = LegacyCode::class;

    public function definition(): array
    {
        return [
            'code' => CodeGenerator::generate('', 7),
            'uuid' => Str::uuid(),
            'plate_id' => null,
            'user_id' => null,
            'medal_id' => null,
            'status' => LegacyCodeStatus::Generated,
        ];
    }
}
