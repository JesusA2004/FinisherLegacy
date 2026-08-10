<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\PlateStatus;
use App\Enums\ResultStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->operator = User::factory()->create();
    $this->operator->assignRole('event_operator');
});

function publishedEditionForOperator(): EventEdition
{
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);

    return $edition->fresh();
}

test('operator routes are blocked without the operator.access permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('operator.index'))->assertForbidden();
});

// --- CAMINO A: INTEGRATED ------------------------------------------------

test('end to end: participant with a result generates an integrated plate, legacy code, real qr, and production job', function () {
    $edition = publishedEditionForOperator();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $athlete = User::factory()->create();

    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'user_id' => $athlete->id,
        'bib_number' => '1234',
    ]);
    EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '03:30:00',
        'status' => ResultStatus::Finished,
    ]);

    $this->actingAs($this->operator)
        ->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);

    $response = $this->actingAs($this->operator)
        ->post(route('operator.participants.plate', $participant));

    $response->assertRedirect();

    $plate = Plate::where('event_participant_id', $participant->id)->firstOrFail();
    expect($plate->generation_mode->value)->toBe('integrated')
        ->and($plate->user_id)->toBe($athlete->id)
        ->and($plate->official_time)->toBe('03:30:00')
        ->and($plate->status)->toBe(PlateStatus::Queued);

    $legacyCode = LegacyCode::where('plate_id', $plate->id)->firstOrFail();
    expect($legacyCode->code)->toStartWith('FL-');

    $qrResponse = $this->get(route('legacy-code.qr', $legacyCode->code));
    $qrResponse->assertOk();
    $qrResponse->assertHeader('Content-Type', 'image/svg+xml');

    expect($plate->productionJobs()->count())->toBe(1);

    // Claiming isn't needed here since the plate is already linked to the
    // athlete (integrated path knows the user up front) — the owner can
    // already see it resolve to their Legacy via the public QR URL.
    $legacyPage = $this->get(route('legacy-code.show', $legacyCode->code));
    $legacyPage->assertOk();
});

test('a participant cannot get two plates', function () {
    $edition = publishedEditionForOperator();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
    ]);

    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);
    $this->actingAs($this->operator)->post(route('operator.participants.plate', $participant));

    $response = $this->actingAs($this->operator)->post(route('operator.participants.plate', $participant));

    $response->assertStatus(409);
    expect(Plate::where('event_participant_id', $participant->id)->count())->toBe(1);
});

// --- CAMINO B: QUICK ------------------------------------------------------

test('end to end: a quick plate has no owner, gets a real qr, and can later be claimed into a Legacy', function () {
    $edition = publishedEditionForOperator();

    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);

    $response = $this->actingAs($this->operator)->post(route('operator.quick-plate'), [
        'athlete_name' => 'Corredor Anónimo',
        'bib_number' => '9999',
    ]);
    $response->assertRedirect();

    $plate = Plate::where('bib_number', '9999')->firstOrFail();
    expect($plate->generation_mode->value)->toBe('quick')
        ->and($plate->user_id)->toBeNull();

    $legacyCode = LegacyCode::where('plate_id', $plate->id)->firstOrFail();
    expect($legacyCode->user_id)->toBeNull()
        ->and($legacyCode->claimed_by_user_id)->toBeNull();

    $this->get(route('legacy-code.qr', $legacyCode->code))->assertOk();

    // Weeks later: the athlete scans the QR and claims it into their Legacy.
    $claimant = User::factory()->create();
    $this->actingAs($claimant)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    expect($legacyCode->fresh()->user_id)->toBe($claimant->id)
        ->and($plate->fresh()->user_id)->toBe($claimant->id)
        ->and(Medal::where('user_id', $claimant->id)->exists())->toBeTrue();
});
