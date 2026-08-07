<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and receive an athlete role and a legacy id', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Jesús',
        'last_name' => 'Ávila',
        'email' => 'jesus@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'jesus@example.com')->firstOrFail();

    expect($user->hasRole('athlete'))->toBeTrue();
    expect($user->legacyId)->not->toBeNull();
    expect($user->legacyId->code)->toStartWith('FL-');
});

test('registration requires matching password confirmation', function () {
    $response = $this->post(route('register.store'), [
        'first_name' => 'Jesús',
        'last_name' => 'Ávila',
        'email' => 'jesus@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});
