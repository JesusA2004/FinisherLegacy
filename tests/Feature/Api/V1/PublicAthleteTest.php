<?php

use App\Models\AthleteProfile;
use App\Models\Medal;

test('a public athlete profile is visible without authentication', function () {
    $profile = AthleteProfile::factory()->create(['username' => 'zurielavila', 'profile_visibility' => 'public']);
    Medal::factory()->create(['user_id' => $profile->user_id, 'visibility' => 'public']);

    $response = $this->getJson('/api/v1/athletes/zurielavila');

    $response->assertOk();
    $response->assertJsonPath('data.profile.username', 'zurielavila');
    $response->assertJsonPath('data.stats.medals', 1);
});

test('a private athlete profile is not exposed via the api', function () {
    $profile = AthleteProfile::factory()->create(['username' => 'privado', 'profile_visibility' => 'private']);

    $this->getJson('/api/v1/athletes/privado')->assertNotFound();
});

test('an unknown username returns 404', function () {
    $this->getJson('/api/v1/athletes/no-existe')->assertNotFound();
});
