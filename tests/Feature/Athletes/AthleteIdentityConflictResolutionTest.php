<?php

use App\Actions\Athletes\ResolveAthleteIdentityConflict;
use App\Enums\AthleteIdentityConflictStatus;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use App\Models\EventParticipant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->action = app(ResolveAthleteIdentityConflict::class);
});

test('resolving a conflict as "same person" links the participant to the chosen athlete', function () {
    $athlete = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['athlete_id' => null]);
    $conflict = AthleteIdentityConflict::factory()->create([
        'event_participant_id' => $participant->id,
        'candidate_athlete_id' => $athlete->id,
    ]);
    $admin = User::factory()->create();

    $resolved = $this->action->handle($conflict, 'link_existing', $admin, $athlete);

    expect($resolved->status)->toBe(AthleteIdentityConflictStatus::Resolved)
        ->and($resolved->resolved_by)->toBe($admin->id)
        ->and($participant->fresh()->athlete_id)->toBe($athlete->id);
});

test('resolving a conflict as "create new" makes a fresh athlete and links the participant to it, never to the candidate', function () {
    $candidate = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez', 'athlete_id' => null]);
    $conflict = AthleteIdentityConflict::factory()->create([
        'event_participant_id' => $participant->id,
        'candidate_athlete_id' => $candidate->id,
        'incoming_data' => ['first_name' => 'Juan', 'last_name' => 'Pérez', 'email' => null, 'birth_date' => null],
    ]);
    $admin = User::factory()->create();
    $athletesBefore = Athlete::count();

    $this->action->handle($conflict, 'create_new', $admin);

    expect(Athlete::count())->toBe($athletesBefore + 1)
        ->and($participant->fresh()->athlete_id)->not->toBe($candidate->id)
        ->and($participant->fresh()->athlete_id)->not->toBeNull();
});

test('ignoring a conflict leaves the participant unlinked and marks the conflict ignored', function () {
    $participant = EventParticipant::factory()->create(['athlete_id' => null]);
    $conflict = AthleteIdentityConflict::factory()->create(['event_participant_id' => $participant->id]);
    $admin = User::factory()->create();

    $resolved = $this->action->handle($conflict, 'ignore', $admin);

    expect($resolved->status)->toBe(AthleteIdentityConflictStatus::Ignored)
        ->and($participant->fresh()->athlete_id)->toBeNull();
});

test('link_existing without any candidate athlete fails validation', function () {
    $conflict = AthleteIdentityConflict::factory()->create(['candidate_athlete_id' => null]);
    $admin = User::factory()->create();

    $this->action->handle($conflict, 'link_existing', $admin);
})->throws(ValidationException::class);

test('the admin identity-conflicts index is blocked without the athletes.manage permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.identity-conflicts.index'))->assertForbidden();
});

test('an admin sees pending identity conflicts with a confidence band, not a raw percentage claim', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    AthleteIdentityConflict::factory()->create(['confidence' => 85]);

    $response = $this->actingAs($admin)->get(route('admin.identity-conflicts.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/identity-conflicts/Index')
        ->has('conflicts.data', 1)
        ->where('conflicts.data.0.confidence_band', 'Alta')
    );
});

test('an admin can resolve a conflict through the HTTP endpoint', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $athlete = Athlete::factory()->create();
    $conflict = AthleteIdentityConflict::factory()->create(['candidate_athlete_id' => $athlete->id]);

    $response = $this->actingAs($admin)->post(route('admin.identity-conflicts.resolve', $conflict), [
        'resolution' => 'link_existing',
        'athlete_id' => $athlete->id,
    ]);

    $response->assertRedirect();
    expect($conflict->fresh()->status)->toBe(AthleteIdentityConflictStatus::Resolved);
});
