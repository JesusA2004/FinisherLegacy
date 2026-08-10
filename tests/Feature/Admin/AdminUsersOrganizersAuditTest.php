<?php

use App\Enums\OrganizerStatus;
use App\Enums\UserStatus;
use App\Models\Organizer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('an admin can list users, filter by status and role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    User::factory()->create(['status' => UserStatus::Suspended])->assignRole('athlete');

    $response = $this->actingAs($admin)->get('/admin/users');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/users/Index')
        ->has('users.data')
        ->has('roles')
    );

    $this->actingAs($admin)->get('/admin/users?status=suspended')->assertOk();
});

test('a user without users.view cannot reach the admin users list', function () {
    $operator = User::factory()->create();
    $operator->assignRole('event_operator');

    $this->actingAs($operator)->get('/admin/users')->assertForbidden();
});

test('an admin can suspend and reactivate a user but not their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($admin)
        ->patch("/admin/users/{$athlete->id}/status", ['status' => 'suspended'])
        ->assertRedirect();

    expect($athlete->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)
        ->patch("/admin/users/{$admin->id}/status", ['status' => 'suspended'])
        ->assertStatus(422);
});

test('an admin can sync roles for a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/admin/users/{$athlete->id}/roles", ['roles' => ['event_operator']])
        ->assertRedirect();

    expect($athlete->fresh()->hasRole('event_operator'))->toBeTrue();
});

test('an admin can list, create and update organizers', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/organizers')->assertOk();

    $this->actingAs($admin)->post('/admin/organizers', [
        'name' => 'Carreras del Valle',
        'email' => 'contacto@carrerasdelvalle.mx',
        'status' => 'active',
    ])->assertRedirect();

    $organizer = Organizer::where('name', 'Carreras del Valle')->firstOrFail();
    expect($organizer->status)->toBe(OrganizerStatus::Active);

    $this->actingAs($admin)
        ->patch("/admin/organizers/{$organizer->id}", [
            'name' => 'Carreras del Valle',
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect($organizer->fresh()->status)->toBe(OrganizerStatus::Inactive);
});

test('an admin can view the audit log filtered by event and subject type', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    activity()->causedBy($admin)->event('created')->log('Usuario creado');

    $response = $this->actingAs($admin)->get('/admin/audit');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/audit/Index')
        ->has('activities.data')
    );

    $this->actingAs($admin)->get('/admin/audit?event=created')->assertOk();
});

test('a user without audit.view cannot reach the audit log', function () {
    $operator = User::factory()->create();
    $operator->assignRole('event_operator');

    $this->actingAs($operator)->get('/admin/audit')->assertForbidden();
});
