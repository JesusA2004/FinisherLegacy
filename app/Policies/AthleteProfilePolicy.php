<?php

namespace App\Policies;

use App\Models\AthleteProfile;
use App\Models\User;

class AthleteProfilePolicy
{
    public function update(User $user, AthleteProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
