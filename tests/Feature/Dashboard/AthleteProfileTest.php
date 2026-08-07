<?php

use App\Enums\ProfileVisibility;
use App\Models\AthleteProfile;
use App\Models\User;

test('an athlete can complete their legacy profile for the first time', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('dashboard.profile.update'), [
        'username' => 'nuevoatleta',
        'bio' => 'Corredor de fondo.',
        'city' => 'CDMX',
        'state' => null,
        'country' => 'México',
        'main_sport_id' => null,
        'profile_visibility' => 'public',
    ]);

    $response->assertRedirect(route('dashboard.profile.edit'));

    expect($user->fresh()->athleteProfile)
        ->username->toBe('nuevoatleta')
        ->city->toBe('CDMX');
});

test('an athlete can update their existing legacy profile', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create(['username' => 'viejonombre']);

    $response = $this->actingAs($user)->patch(route('dashboard.profile.update'), [
        'username' => 'nuevonombre',
        'bio' => null,
        'city' => null,
        'state' => null,
        'country' => null,
        'main_sport_id' => null,
        'profile_visibility' => 'private',
    ]);

    $response->assertSessionHasNoErrors();

    expect($profile->fresh())
        ->username->toBe('nuevonombre')
        ->profile_visibility->toBe(ProfileVisibility::Private);
});

test('username must be unique across athlete profiles', function () {
    AthleteProfile::factory()->create(['username' => 'tomado']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('dashboard.profile.update'), [
        'username' => 'tomado',
        'profile_visibility' => 'public',
    ]);

    $response->assertSessionHasErrors('username');
});

test('a guest cannot update an athlete profile', function () {
    $response = $this->patch(route('dashboard.profile.update'), [
        'username' => 'intruso',
        'profile_visibility' => 'public',
    ]);

    $response->assertRedirect(route('login'));
});
