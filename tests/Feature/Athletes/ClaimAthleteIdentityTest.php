<?php

use App\Actions\Athletes\EnsureAthleteForUser;
use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Models\Athlete;
use App\Models\AthleteIdentityConflict;
use App\Models\EventParticipant;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\User;

function createAssignedLegacyCodeForAthleteTests(array $plateAttributes = [], ?EventParticipant $participant = null): LegacyCode
{
    $plate = Plate::factory()->create(array_merge([
        'generation_mode' => $participant !== null ? PlateGenerationMode::Integrated : PlateGenerationMode::Quick,
        'event_participant_id' => $participant?->id,
        'event_name' => 'Maratón de Prueba',
        'race_name' => '21K',
        'official_time' => '01:45:10',
    ], $plateAttributes));

    $legacyCode = LegacyCode::factory()->create([
        'plate_id' => $plate->id,
        'status' => LegacyCodeStatus::Assigned,
    ]);

    $plate->update(['legacy_code_id' => $legacyCode->id]);

    return $legacyCode->fresh(['plate']);
}

test('claiming an integrated plate whose participant already has an athlete links the user to that same athlete', function () {
    $athlete = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['athlete_id' => $athlete->id]);
    $legacyCode = createAssignedLegacyCodeForAthleteTests([], $participant);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    expect($user->fresh()->athlete->id)->toBe($athlete->id)
        ->and($legacyCode->plate->fresh()->athlete_id)->toBe($athlete->id);
});

test('claiming a quick plate with no participant creates a fresh athlete for a user who had none', function () {
    $legacyCode = createAssignedLegacyCodeForAthleteTests(['generation_mode' => PlateGenerationMode::Quick]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    expect($user->fresh()->athlete)->not->toBeNull()
        ->and($legacyCode->plate->fresh()->athlete_id)->toBe($user->fresh()->athlete->id);
});

test('claiming reuses the athlete the user already has instead of creating a second one', function () {
    $user = User::factory()->create();
    $existingAthlete = app(EnsureAthleteForUser::class)->handle($user, 'registration');
    $legacyCode = createAssignedLegacyCodeForAthleteTests(['generation_mode' => PlateGenerationMode::Quick]);
    $athletesBefore = Athlete::count();

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    expect(Athlete::count())->toBe($athletesBefore)
        ->and($legacyCode->plate->fresh()->athlete_id)->toBe($existingAthlete->id);
});

test('claiming a plate whose participant athlete differs from the user own athlete aborts the whole claim and records a conflict', function () {
    $participantAthlete = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['athlete_id' => $participantAthlete->id]);
    $legacyCode = createAssignedLegacyCodeForAthleteTests([], $participant);

    $user = User::factory()->create();
    app(EnsureAthleteForUser::class)->handle($user, 'registration');
    $userAthleteId = $user->fresh()->athlete->id;

    expect($userAthleteId)->not->toBe($participantAthlete->id);

    $response = $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code));

    $response->assertSessionHas('inertia.flash_data.toast.type', 'error');
    expect($legacyCode->fresh()->status)->toBe(LegacyCodeStatus::Assigned)
        ->and($legacyCode->fresh()->claimed_by_user_id)->toBeNull()
        ->and($legacyCode->plate->fresh()->athlete_id)->toBeNull()
        ->and(AthleteIdentityConflict::where('source_type', 'claim')->count())->toBe(1);
});

test('a plate frozen snapshot never changes when the linked athlete is renamed afterward', function () {
    $athlete = Athlete::factory()->create();
    $participant = EventParticipant::factory()->create(['athlete_id' => $athlete->id]);
    $legacyCode = createAssignedLegacyCodeForAthleteTests(['athlete_name' => 'Nombre Original En Placa'], $participant);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    $athlete->update(['first_name' => 'NuevoNombre', 'last_name' => 'NuevoApellido', 'full_name' => 'NuevoNombre NuevoApellido']);

    expect($legacyCode->plate->fresh()->athlete_name)->toBe('Nombre Original En Placa');
});
