<?php

use App\Enums\OrganizerStatus;
use App\Enums\UserStatus;
use App\Models\Organizer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

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
        ->patchJson("/admin/users/{$athlete->id}/status", ['status' => 'suspended'])
        ->assertRedirect();

    expect($athlete->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)
        ->patchJson("/admin/users/{$admin->id}/status", ['status' => 'suspended'])
        ->assertStatus(422);
});

test('an admin can sync roles for a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = User::factory()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/users/{$athlete->id}/roles", ['roles' => ['event_operator']])
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

test('an athlete cannot reach any of the three new admin sections', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('athlete');

    $this->actingAs($athlete)->get('/admin/users')->assertForbidden();
    $this->actingAs($athlete)->get('/admin/organizers')->assertForbidden();
    $this->actingAs($athlete)->get('/admin/audit')->assertForbidden();
});

test('an event_operator without organizers.view cannot reach admin organizers', function () {
    $operator = User::factory()->create();
    $operator->assignRole('event_operator');

    $this->actingAs($operator)->get('/admin/organizers')->assertForbidden();
});

test('a super_admin reaches all three new admin sections via the Gate::before bypass', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin)->get('/admin/users')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/organizers')->assertOk();
    $this->actingAs($superAdmin)->get('/admin/audit')->assertOk();
});

test('a regular admin cannot grant the super_admin role to another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = User::factory()->create();

    $this->actingAs($admin)
        ->patchJson("/admin/users/{$athlete->id}/roles", ['roles' => ['super_admin']])
        ->assertStatus(422);

    expect($athlete->fresh()->hasRole('super_admin'))->toBeFalse();
});

test('a super_admin can grant the super_admin role to another user', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $athlete = User::factory()->create();

    $this->actingAs($superAdmin)
        ->patchJson("/admin/users/{$athlete->id}/roles", ['roles' => ['super_admin']])
        ->assertRedirect();

    expect($athlete->fresh()->hasRole('super_admin'))->toBeTrue();
});

test('the last active super_admin cannot be demoted or suspended, leaving another one is fine', function () {
    $superAdmin = User::factory()->create(['status' => UserStatus::Active]);
    $superAdmin->assignRole('super_admin');
    $secondSuperAdmin = User::factory()->create(['status' => UserStatus::Active]);
    $secondSuperAdmin->assignRole('super_admin');

    // Cannot remove the role while it would leave zero active super_admins.
    $secondSuperAdmin->status = UserStatus::Suspended;
    $secondSuperAdmin->save();

    $this->actingAs($superAdmin)
        ->patchJson("/admin/users/{$superAdmin->id}/roles", ['roles' => []])
        ->assertStatus(422);

    expect($superAdmin->fresh()->hasRole('super_admin'))->toBeTrue();

    // With a second active super_admin, demoting the first one is fine.
    $secondSuperAdmin->status = UserStatus::Active;
    $secondSuperAdmin->save();

    $this->actingAs($secondSuperAdmin)
        ->patchJson("/admin/users/{$superAdmin->id}/roles", ['roles' => []])
        ->assertRedirect();

    expect($superAdmin->fresh()->hasRole('super_admin'))->toBeFalse();
});

test('suspending the only active super_admin is blocked', function () {
    $superAdmin = User::factory()->create(['status' => UserStatus::Active]);
    $superAdmin->assignRole('super_admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->patchJson("/admin/users/{$superAdmin->id}/status", ['status' => 'suspended'])
        ->assertStatus(422);

    expect($superAdmin->fresh()->status)->toBe(UserStatus::Active);
});

test('status and role changes on a user are recorded in the audit log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($admin)->patchJson("/admin/users/{$athlete->id}/status", ['status' => 'suspended']);
    $this->actingAs($admin)->patchJson("/admin/users/{$athlete->id}/roles", ['roles' => ['event_operator']]);

    expect(Activity::where('subject_id', $athlete->id)
        ->where('subject_type', User::class)
        ->count())->toBe(2);
});

test('an admin can upload and replace an organizer logo', function () {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post('/admin/organizers', [
        'name' => 'Trail Runners MX',
        'status' => 'active',
        'logo' => UploadedFile::fake()->image('logo.jpg', 600, 600),
    ])->assertRedirect();

    $organizer = Organizer::where('name', 'Trail Runners MX')->firstOrFail();
    expect($organizer->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($organizer->logo_path);

    $this->actingAs($admin)->post("/admin/organizers/{$organizer->id}", [
        '_method' => 'patch',
        'name' => 'Trail Runners MX',
        'status' => 'active',
        'logo' => UploadedFile::fake()->image('logo2.jpg', 600, 600),
    ])->assertRedirect();

    expect($organizer->fresh()->logo_path)->not->toBeNull();
});
