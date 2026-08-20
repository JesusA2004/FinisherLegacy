<?php

use App\Actions\Athletes\IngestEventParticipant;
use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Models\Athlete;
use App\Models\EventEdition;
use App\Models\EventRace;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// --- IMPORT: dos filas del mismo evento/persona nunca duplican el Athlete --

test('ingesting the same participant twice through the import pipeline never creates a second athlete', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $first = app(IngestEventParticipant::class)->handle([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'bib_number' => '101',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ], 'import', 'file-1.csv');

    $second = app(IngestEventParticipant::class)->handle([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'bib_number' => '101',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
    ], 'import', 'file-1.csv');

    expect($first->id)->toBe($second->id)
        ->and(Athlete::count())->toBe(1)
        ->and($second->athlete_id)->toBe($first->athlete_id);
});

test('importing the same person into two different events links both participations to one athlete', function () {
    $eventA = EventEdition::factory()->create();
    $eventB = EventEdition::factory()->create();
    $raceA = EventRace::factory()->create(['event_edition_id' => $eventA->id]);
    $raceB = EventRace::factory()->create(['event_edition_id' => $eventB->id]);

    $participantA = app(IngestEventParticipant::class)->handle([
        'event_edition_id' => $eventA->id,
        'event_race_id' => $raceA->id,
        'bib_number' => '142',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan.perez@example.com',
    ], 'import');

    $participantB = app(IngestEventParticipant::class)->handle([
        'event_edition_id' => $eventB->id,
        'event_race_id' => $raceB->id,
        'bib_number' => '992',
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan.perez@example.com',
    ], 'import');

    expect($participantA->athlete_id)->not->toBeNull()
        ->and($participantA->athlete_id)->toBe($participantB->athlete_id)
        ->and(Athlete::count())->toBe(1);
});

test('importing the same small CSV twice via the real HTTP upload flow does not duplicate the athlete', function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);
    $manager = User::factory()->create();
    $manager->assignRole('event_manager');

    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);
    EventRace::factory()->create(['event_edition_id' => $edition->id, 'name' => '10K']);

    $mapping = ['bib_number' => 0, 'first_name' => 1, 'last_name' => 2, 'race_name' => 3, 'email' => 4];
    $csv = fn () => UploadedFile::fake()->createWithContent('participants.csv', implode("\n", [
        'Bib,Nombre,Apellido,Distancia,Correo',
        '101,Ada,Lovelace,10K,ada@example.com',
    ]));

    foreach ([1, 2] as $_) {
        $upload = $this->actingAs($manager)->postJson(route('imports.upload'), ['file' => $csv()]);

        $this->actingAs($manager)->post(route('imports.store'), [
            'event_edition_id' => $edition->id,
            'temp_path' => $upload->json('data.temp_path'),
            'original_filename' => $upload->json('data.original_filename'),
            'mapping' => $mapping,
        ]);
    }

    expect(Athlete::where('normalized_email', 'ada@example.com')->count())->toBe(1);
});

// --- REGISTRO: una cuenta nueva con el email de un Athlete ya importado ---

test('a new account registering with the exact email of an athlete already imported from a roster links to it, never duplicates', function () {
    $existing = Athlete::factory()->create(['email' => 'ada@example.com', 'normalized_email' => 'ada@example.com', 'user_id' => null]);

    $user = User::factory()->create(['email' => 'ada@example.com']);
    $athlete = app(\App\Actions\Athletes\EnsureAthleteForUser::class)->handle($user, 'registration');

    expect($athlete->id)->toBe($existing->id)
        ->and(Athlete::count())->toBe(1);
});

test('granting the athlete role to a user through the admin panel resolves an athlete for them', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update-roles', $target), ['roles' => ['athlete']])->assertRedirect();

    expect($target->fresh()->athlete)->not->toBeNull();
});
