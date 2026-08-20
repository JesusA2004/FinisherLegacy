<?php

namespace App\Actions\Athletes;

use App\Enums\AthleteIdentityStatus;
use App\Models\Athlete;
use App\Support\Athletes\AthleteIdentityCandidateData;
use Illuminate\Support\Str;

/**
 * The only place `Athlete::create()` is called outside factories/tests —
 * see docs/adr/0004-athlete-canonical-identity.md §31.
 */
class CreateAthlete
{
    public function handle(AthleteIdentityCandidateData $data, ?int $userId = null): Athlete
    {
        return Athlete::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'full_name' => $data->fullName,
            'normalized_first_name' => $data->normalizedFirstName,
            'normalized_last_name' => $data->normalizedLastName,
            'normalized_full_name' => $data->normalizedFullName,
            'email' => $data->email,
            'normalized_email' => $data->normalizedEmail,
            'phone' => $data->phone,
            'normalized_phone' => $data->normalizedPhone,
            'birth_date' => $data->birthDate,
            'gender' => $data->gender,
            'country' => $data->country,
            'identity_status' => AthleteIdentityStatus::Active,
        ]);
    }
}
