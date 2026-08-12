<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('users.manage can create a user with roles', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'first_name' => 'Nueva',
        'last_name' => 'Cuenta',
        'email' => 'nueva.cuenta@example.com',
        'phone' => null,
        'password' => 'Cambia-Esto-123!',
        'password_confirmation' => 'Cambia-Esto-123!',
        'status' => 'active',
        'roles' => ['event_operator'],
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $user = User::where('email', 'nueva.cuenta@example.com')->firstOrFail();
    expect($user->hasRole('event_operator'))->toBeTrue();
});

test('without users.manage, creating a user is forbidden', function () {
    $operator = User::factory()->create();
    $operator->assignRole('event_operator');

    $this->actingAs($operator)->post(route('admin.users.store'), [
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.com',
        'password' => 'Cambia-Esto-123!', 'password_confirmation' => 'Cambia-Esto-123!', 'status' => 'active',
    ])->assertForbidden();
});

test('a non-super_admin cannot grant the super_admin role when creating a user', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'first_name' => 'Intento', 'last_name' => 'Escalar', 'email' => 'escalar@example.com',
        'password' => 'Cambia-Esto-123!', 'password_confirmation' => 'Cambia-Esto-123!', 'status' => 'active',
        'roles' => ['super_admin'],
    ]);

    $response->assertSessionHasErrors('roles');
    expect(User::where('email', 'escalar@example.com')->exists())->toBeFalse();
});

test('admin can edit a user\'s core fields', function () {
    $user = User::factory()->create(['first_name' => 'Antes']);

    $this->actingAs($this->admin)->patch(route('admin.users.update', $user), [
        'first_name' => 'Después',
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone' => '555-0000',
    ])->assertRedirect();

    expect($user->fresh()->first_name)->toBe('Después')
        ->and($user->fresh()->phone)->toBe('555-0000');
});

test('admin can reset a user\'s password and the user can log in with it', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user), [
        'password' => 'Nueva-Clave-456!',
        'password_confirmation' => 'Nueva-Clave-456!',
    ])->assertRedirect();

    expect(Hash::check('Nueva-Clave-456!', $user->fresh()->password))->toBeTrue();
});
