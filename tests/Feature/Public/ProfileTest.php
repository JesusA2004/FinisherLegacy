<?php

use App\Models\AthleteProfile;
use App\Models\Medal;
use App\Models\User;

test('a public legacy profile is visible to guests', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create([
        'username' => 'perfilpublico',
        'profile_visibility' => 'public',
    ]);

    $response = $this->get("/@{$profile->username}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('profile/Show')
        ->where('profile.username', 'perfilpublico')
    );
});

test('a private legacy profile is protected from guests', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create([
        'username' => 'perfilprivado',
        'profile_visibility' => 'private',
    ]);

    $response = $this->get("/@{$profile->username}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('profile/Private'));
});

test('the owner can still see their own private legacy profile', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create([
        'username' => 'miperfilprivado',
        'profile_visibility' => 'private',
    ]);

    $response = $this->actingAs($user)->get("/@{$profile->username}");

    $response->assertInertia(fn ($page) => $page->component('profile/Show'));
});

test('a public legacy profile only shows public medals', function () {
    $user = User::factory()->create();
    $profile = AthleteProfile::factory()->for($user)->create([
        'username' => 'coleccionista',
        'profile_visibility' => 'public',
    ]);

    Medal::factory()->for($user)->create(['title' => 'Medalla pública', 'visibility' => 'public']);
    Medal::factory()->for($user)->create(['title' => 'Medalla privada', 'visibility' => 'private']);

    $response = $this->get("/@{$profile->username}");

    $response->assertInertia(fn ($page) => $page
        ->component('profile/Show')
        ->has('medals', 1)
        ->where('medals.0.title', 'Medalla pública')
    );
});

test('an unknown username returns 404', function () {
    $response = $this->get('/@no-existe-este-usuario');

    $response->assertNotFound();
});
