<?php

namespace App\Actions\Fortify;

use App\Actions\Athletes\EnsureAthleteForUser;
use App\Concerns\PasswordValidationRules;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\LegacyIdService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'status' => UserStatus::Active,
            ]);

            $user->assignRole('athlete');

            app(LegacyIdService::class)->issueFor($user);

            // An Athlete already exists from an event import with this
            // exact email? Link to it — never create a duplicate. See
            // docs/adr/0004-athlete-canonical-identity.md §32-33.
            app(EnsureAthleteForUser::class)->handle($user, 'registration');

            return $user;
        });
    }
}
