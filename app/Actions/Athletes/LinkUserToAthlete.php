<?php

namespace App\Actions\Athletes;

use App\Exceptions\Athletes\AthleteAlreadyLinkedException;
use App\Models\Athlete;
use App\Models\User;

class LinkUserToAthlete
{
    /**
     * @throws AthleteAlreadyLinkedException if the user already has a different Athlete
     */
    public function handle(User $user, Athlete $athlete): Athlete
    {
        // A fresh query, never the cached `$user->athlete` relation: this
        // action is sometimes called more than once against the same $user
        // instance within one request/chain (e.g. App\Actions\Athletes\
        // EnsureAthleteForUser calling it right after its own `$user->athlete`
        // read), and a stale cached null would let two different Athlete rows
        // both try to claim this user's unique `user_id` column.
        $existing = $user->athlete()->first();

        if ($existing !== null && $existing->id !== $athlete->id) {
            throw new AthleteAlreadyLinkedException;
        }

        if ($athlete->user_id === null) {
            $athlete->update(['user_id' => $user->id]);
        }

        return $athlete->fresh();
    }
}
