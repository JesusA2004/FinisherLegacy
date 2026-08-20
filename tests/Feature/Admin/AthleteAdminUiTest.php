<?php

use App\Models\Athlete;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('the athletes index is blocked without the athletes.view permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.athletes.index'))->assertForbidden();
});

test('an admin sees the athlete list with event counts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = Athlete::factory()->create(['full_name' => 'Juan Pérez']);
    EventParticipant::factory()->count(3)->create(['athlete_id' => $athlete->id]);

    $response = $this->actingAs($admin)->get(route('admin.athletes.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/athletes/Index')
        ->has('athletes.data', 1)
        ->where('athletes.data.0.event_count', 3)
        ->where('athletes.data.0.full_name', 'Juan Pérez')
    );
});

test('an admin can search athletes by email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Athlete::factory()->create(['full_name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'normalized_email' => 'ada@example.com']);
    Athlete::factory()->create(['full_name' => 'Alan Turing', 'email' => 'alan@example.com', 'normalized_email' => 'alan@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.athletes.index', ['q' => 'ada@example.com']));

    $response->assertInertia(fn ($page) => $page
        ->has('athletes.data', 1)
        ->where('athletes.data.0.full_name', 'Ada Lovelace')
    );
});

test('the athlete detail page shows every event participation — the visual proof of one athlete across many events', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = Athlete::factory()->create(['full_name' => 'Juan Pérez']);
    $eventA = EventEdition::factory()->create();
    $eventB = EventEdition::factory()->create();
    $eventC = EventEdition::factory()->create();

    EventParticipant::factory()->create(['athlete_id' => $athlete->id, 'event_edition_id' => $eventA->id, 'bib_number' => '142']);
    EventParticipant::factory()->create(['athlete_id' => $athlete->id, 'event_edition_id' => $eventB->id, 'bib_number' => '992']);
    EventParticipant::factory()->create(['athlete_id' => $athlete->id, 'event_edition_id' => $eventC->id, 'bib_number' => '31']);

    $response = $this->actingAs($admin)->get(route('admin.athletes.show', $athlete));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/athletes/Show')
        ->where('athlete.id', $athlete->id)
        ->has('participations', 3)
    );
});

test('a nonexistent athlete returns 404', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/athletes/999999')->assertNotFound();
});
