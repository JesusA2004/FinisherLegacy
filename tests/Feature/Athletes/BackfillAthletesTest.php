<?php

use App\Enums\AthleteIdentityConflictStatus;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use App\Models\EventParticipant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('dry-run never writes any athlete or link to the database', function () {
    $user = User::factory()->create();
    $user->assignRole('athlete');
    EventParticipant::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'athlete_id' => null]);

    $this->artisan('finisher:backfill-athletes', ['--dry-run' => true])->assertExitCode(0);

    expect(Athlete::count())->toBe(0)
        ->and(EventParticipant::whereNotNull('athlete_id')->count())->toBe(0);
});

test('apply creates an athlete for a user with the athlete role and has no other flag', function () {
    $user = User::factory()->create();
    $user->assignRole('athlete');

    $this->artisan('finisher:backfill-athletes', ['--apply' => true])->assertExitCode(0);

    expect($user->fresh()->athlete)->not->toBeNull();
});

test('apply links a participant with a known user to that user own athlete', function () {
    $user = User::factory()->create();
    $user->assignRole('athlete');
    $participant = EventParticipant::factory()->create(['user_id' => $user->id, 'athlete_id' => null]);

    $this->artisan('finisher:backfill-athletes', ['--apply' => true])->assertExitCode(0);

    expect($participant->fresh()->athlete_id)->toBe($user->fresh()->athlete->id);
});

test('apply resolves remaining participants through the same matcher used everywhere else, by email', function () {
    $existing = Athlete::factory()->create(['email' => 'shared@example.com', 'normalized_email' => 'shared@example.com']);
    $participant = EventParticipant::factory()->create(['email' => 'shared@example.com', 'athlete_id' => null]);

    $this->artisan('finisher:backfill-athletes', ['--apply' => true])->assertExitCode(0);

    expect($participant->fresh()->athlete_id)->toBe($existing->id);
});

test('requires exactly one of --dry-run or --apply', function () {
    $this->artisan('finisher:backfill-athletes')->assertExitCode(1);
});

test('running apply twice on the same dataset is idempotent: same athlete counts, no duplicate conflicts', function () {
    EventParticipant::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'athlete_id' => null]);
    EventParticipant::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'athlete_id' => null]);

    $this->artisan('finisher:backfill-athletes', ['--apply' => true])->assertExitCode(0);
    $firstAthleteCount = Athlete::count();
    $firstConflictCount = AthleteIdentityConflict::count();

    $this->artisan('finisher:backfill-athletes', ['--apply' => true])->assertExitCode(0);

    expect(Athlete::count())->toBe($firstAthleteCount)
        ->and(AthleteIdentityConflict::count())->toBe($firstConflictCount)
        ->and(AthleteIdentityConflict::where('status', AthleteIdentityConflictStatus::Pending)->count())->toBe($firstConflictCount);
});
