<?php

use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

function apiOperatorAuthHeader(): array
{
    $user = User::factory()->create();
    $user->assignRole('event_operator');

    return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('event ops routes are blocked without a user token', function () {
    $edition = EventEdition::factory()->create();

    $this->getJson("/api/v1/event-ops/{$edition->id}")->assertUnauthorized();
});

test('event ops routes are blocked without operator.access', function () {
    $user = User::factory()->create();
    $headers = ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    $edition = EventEdition::factory()->create();

    $this->withHeaders($headers)->getJson("/api/v1/event-ops/{$edition->id}")->assertForbidden();
});

/**
 * The full docs/adr/0006 criterion, over HTTP only — no Inertia, no
 * session: authenticate, dashboard, search bib, participant detail +
 * eligibility, generate plate, ProductionJob queued.
 */
test('the full event ops criterion works end to end via the API alone', function () {
    $headers = apiOperatorAuthHeader();

    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id, 'event_race_id' => $race->id,
        'bib_number' => '1425', 'first_name' => 'Zuriel', 'last_name' => 'Ávila', 'full_name' => 'Zuriel Ávila',
    ]);
    EventResult::factory()->create(['event_participant_id' => $participant->id, 'official_time' => '05:21:18']);
    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $dashboard = $this->withHeaders($headers)->getJson("/api/v1/event-ops/{$edition->id}");
    $dashboard->assertOk();
    $dashboard->assertJsonStructure(['data' => ['provider', 'data', 'production', 'stations', 'readiness', 'metrics']]);
    expect($dashboard->json('data.data.participants'))->toBe(1);

    $search = $this->withHeaders($headers)->getJson("/api/v1/event-ops/{$edition->id}/participants/search?q=1425");
    $search->assertOk();
    expect($search->json('data.0.bib_number'))->toBe('1425')
        ->and($search->json('data.0.full_name'))->toBe('Zuriel Ávila');

    $detail = $this->withHeaders($headers)->getJson("/api/v1/event-ops/participants/{$participant->id}");
    $detail->assertOk();
    expect($detail->json('data.result.official_time'))->toBe('05:21:18')
        ->and($detail->json('data.eligibility.eligible'))->toBeTrue()
        ->and($detail->json('data.eligibility.reasons'))->toBe([]);

    $generate = $this->withHeaders($headers)->postJson("/api/v1/event-ops/participants/{$participant->id}/plate");
    $generate->assertCreated();
    expect(Plate::where('event_participant_id', $participant->id)->count())->toBe(1);

    $plateId = $generate->json('data.id');
    $job = ProductionJob::where('plate_id', $plateId)->firstOrFail();
    expect($job->status->value)->toBe('queued');
});

test('generating the same plate twice via the API returns a 409 with a machine-readable code', function () {
    $headers = apiOperatorAuthHeader();
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    EventResult::factory()->create(['event_participant_id' => $participant->id]);
    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $this->withHeaders($headers)->postJson("/api/v1/event-ops/participants/{$participant->id}/plate")->assertCreated();
    $second = $this->withHeaders($headers)->postJson("/api/v1/event-ops/participants/{$participant->id}/plate");

    $second->assertStatus(409);
    expect($second->json('error.code'))->toBe('PLATE_ALREADY_EXISTS')
        ->and(Plate::where('event_participant_id', $participant->id)->count())->toBe(1);
});

test('generating a plate with an Idempotency-Key replays the same plate instead of a 409', function () {
    $headers = apiOperatorAuthHeader();
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    EventResult::factory()->create(['event_participant_id' => $participant->id]);
    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $withKey = [...$headers, 'Idempotency-Key' => 'demo-key-1'];

    $first = $this->withHeaders($withKey)->postJson("/api/v1/event-ops/participants/{$participant->id}/plate");
    $second = $this->withHeaders($withKey)->postJson("/api/v1/event-ops/participants/{$participant->id}/plate");

    $first->assertCreated();
    $second->assertCreated();
    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Plate::where('event_participant_id', $participant->id)->count())->toBe(1);
});

test('a participant with no result is not eligible via the API either', function () {
    $headers = apiOperatorAuthHeader();
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);

    $detail = $this->withHeaders($headers)->getJson("/api/v1/event-ops/participants/{$participant->id}");

    $detail->assertOk();
    expect($detail->json('data.eligibility.eligible'))->toBeFalse()
        ->and($detail->json('data.eligibility.reasons'))->toContain('NO_RESULT');
});

test('search never leaks email, phone, or birth date', function () {
    $headers = apiOperatorAuthHeader();
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    EventParticipant::factory()->create([
        'event_edition_id' => $edition->id, 'event_race_id' => $race->id,
        'bib_number' => '77', 'email' => 'secret@example.test', 'phone' => '5555555555',
    ]);

    $response = $this->withHeaders($headers)->getJson("/api/v1/event-ops/{$edition->id}/participants/search?q=77");

    $response->assertOk();
    $response->assertJsonMissingPath('data.0.email');
    $response->assertJsonMissingPath('data.0.phone');
    expect($response->getContent())->not->toContain('secret@example.test');
});
