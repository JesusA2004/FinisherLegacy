<?php

use App\Models\AthleteProfile;
use App\Models\User;

test('a user can fetch their own profile', function () {
    $user = User::factory()->create();
    AthleteProfile::factory()->create(['user_id' => $user->id, 'username' => 'ada']);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/profile');

    $response->assertOk();
    $response->assertJsonPath('data.username', 'ada');
});

test('a user without a profile yet gets null data instead of an error', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/profile');

    $response->assertOk();
    $response->assertJsonPath('data', null);
});

test('a user can update their profile via the api', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->patchJson('/api/v1/profile', [
        'username' => 'zurielavila',
        'profile_visibility' => 'public',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.username', 'zurielavila');
    expect($user->fresh()->athleteProfile->username)->toBe('zurielavila');
});

test('profile requires authentication', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
});
