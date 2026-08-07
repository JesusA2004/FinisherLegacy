<?php

namespace App\Policies;

use App\Models\Medal;
use App\Models\User;

class MedalPolicy
{
    public function view(User $user, Medal $medal): bool
    {
        return $user->id === $medal->user_id;
    }

    public function update(User $user, Medal $medal): bool
    {
        return $user->id === $medal->user_id;
    }

    public function delete(User $user, Medal $medal): bool
    {
        return $user->id === $medal->user_id;
    }
}
