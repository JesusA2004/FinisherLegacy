<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('a user can register via the api and receives a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['data' => ['user' => ['id', 'email'], 'token'], 'message', 'meta']);

    $user = User::where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->legacyId)->not->toBeNull();
});

test('registration validates input the same way the web form does', function () {
    $response = $this->postJson('/api/v1/auth/register', ['email' => 'not-an-email']);

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('a user can log in via the api and receives a token', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.user.email', $user->email);
    expect($response->json('data.token'))->toBeString();
});

test('login fails with the correct message for wrong credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('protected endpoints reject requests without a token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('me returns the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me');

    $response->assertOk();
    $response->assertJsonPath('data.id', $user->id);
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(PersonalAccessToken::query()->count())->toBe(0);
});
