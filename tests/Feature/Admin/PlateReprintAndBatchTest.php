<?php

use App\Enums\DeviceAbility;
use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ProductionJobStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Models\User;
use App\Services\PlateGenerationService;
use App\Support\CodeGenerator;
use App\Support\PlateFilename;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $template = PlateTemplate::factory()->create();
    $this->version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 40, 'height_mm' => 6, 'font_size_pt' => 8],
        ]],
    ]);
});

function makeReadyPlate(PlateTemplateVersion $version): Plate
{
    $plate = Plate::factory()->create([
        'plate_template_id' => $version->plate_template_id,
        'plate_template_version_id' => $version->id,
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => PlateStatus::Ready,
    ]);

    $legacyCode = LegacyCode::create([
        'code' => CodeGenerator::unique('FL', fn ($c) => LegacyCode::where('code', $c)->exists()),
        'uuid' => Str::uuid(),
        'plate_id' => $plate->id,
        'status' => LegacyCodeStatus::Assigned,
        'assigned_at' => now(),
    ]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);

    ProductionJob::create([
        'plate_id' => $plate->id,
        'status' => ProductionJobStatus::Ready,
        'queued_at' => now(),
    ]);

    return $plate->fresh();
}

test('admin can view plate detail with template, legacy code and production status', function () {
    $plate = makeReadyPlate($this->version);

    $response = $this->actingAs($this->admin)->get(route('admin.plates.show', $plate));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/plates/Show')
        ->where('plate.id', $plate->id)
        ->where('plate.legacy_code', $plate->legacyCode->code)
        ->where('plate.can_reprint', true)
    );
});

test('plate detail exposes result splits sorted by sequence when the result has them', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);

    EventPlateTemplate::create([
        'event_edition_id' => $edition->id,
        'plate_template_version_id' => $this->version->id,
        'is_default' => true,
        'active' => true,
    ]);

    $race = EventRace::factory()->create([
        'event_edition_id' => $edition->id,
        'name' => '42K',
        'distance_value' => 42.195,
        'distance_unit' => 'km',
    ]);
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
    ]);
    $result = EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '03:47:21',
    ]);
    $result->splits()->createMany([
        ['type' => 'split', 'label' => '10K', 'sequence' => 2, 'distance_value' => 10, 'distance_unit' => 'km', 'elapsed_time' => '00:48:12'],
        ['type' => 'split', 'label' => '5K', 'sequence' => 1, 'distance_value' => 5, 'distance_unit' => 'km', 'elapsed_time' => '00:23:40'],
    ]);

    $plate = app(PlateGenerationService::class)->generateIntegrated($participant->fresh(['eventRace', 'result.splits']));

    $response = $this->actingAs($this->admin)->get(route('admin.plates.show', $plate));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/plates/Show')
        ->where('splits.0.label', '5K')
        ->where('splits.0.elapsed_time', '00:23:40')
        ->where('splits.1.label', '10K')
    );
});

test('plate detail exposes an empty splits list when the result has none', function () {
    $plate = makeReadyPlate($this->version);

    $response = $this->actingAs($this->admin)->get(route('admin.plates.show', $plate));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where('splits', []));
});

test('reprinting a ready plate keeps the same legacy code and creates a new production job', function () {
    $plate = makeReadyPlate($this->version);
    $originalCode = $plate->legacyCode->code;

    $response = $this->actingAs($this->admin)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'El grabado salió con un rayón.',
        'use_original' => true,
    ]);

    $response->assertRedirect();

    $plate->refresh();
    expect($plate->legacyCode->code)->toBe($originalCode)
        ->and(LegacyCode::where('plate_id', $plate->id)->count())->toBe(1)
        ->and($plate->status->value)->toBe('queued')
        ->and($plate->reprints()->count())->toBe(1)
        ->and($plate->productionJobs()->count())->toBe(2);
});

test('reprinting starts a fresh production job with an empty checklist', function () {
    $plate = makeReadyPlate($this->version);
    $originalJob = $plate->latestProductionJob;
    $originalJob->update([
        'front_engraved_at' => now(), 'front_engraved_by' => $this->admin->id,
        'back_engraved_at' => now(), 'back_engraved_by' => $this->admin->id,
        'qr_verified_at' => now(), 'qr_verified_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'Placa dañada en tránsito',
        'use_original' => true,
    ])->assertRedirect();

    $newJob = $plate->fresh()->latestProductionJob;
    expect($newJob->id)->not->toBe($originalJob->id)
        ->and($newJob->checklistComplete())->toBeFalse()
        ->and($newJob->front_engraved_at)->toBeNull()
        ->and($newJob->back_engraved_at)->toBeNull()
        ->and($newJob->qr_verified_at)->toBeNull();
});

test('a draft plate cannot be reprinted', function () {
    $plate = Plate::factory()->create(['status' => PlateStatus::Draft]);

    $this->actingAs($this->admin)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'x',
        'use_original' => true,
    ])->assertStatus(422);
});

test('event_operator cannot reprint (plates.manage required, only plates.view granted for that action)', function () {
    $plate = makeReadyPlate($this->version);
    $operator = User::factory()->create();
    $operator->assignRole('event_operator');

    // event_operator lacks dashboard.admin.view entirely, so this 403s at the outer gate.
    $this->actingAs($operator)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'x',
        'use_original' => true,
    ])->assertForbidden();
});

test('a reprint job gets its own fresh artifact, same legacy code and template version, once claimed', function () {
    // makeReadyPlate's original job is already `ready` (grabado y
    // entregable) — not claimable itself, which is correct: only the
    // reprint (a fresh `queued` job) should be.
    $plate = makeReadyPlate($this->version);
    $originalJob = $plate->latestProductionJob;
    $device = ProductionDevice::factory()->create();
    $token = $device->createToken('test', DeviceAbility::all())->plainTextToken;

    $this->actingAs($this->admin)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'Placa dañada en tránsito',
        'use_original' => true,
    ])->assertRedirect();

    // Not `latestProductionJob` here: both jobs can share the same
    // second-precision `created_at` in a fast test run, which makes that
    // relation's tiebreak ambiguous — resolve the new one unambiguously
    // by excluding the known original id instead.
    $reprintJob = ProductionJob::where('plate_id', $plate->id)->where('id', '!=', $originalJob->id)->firstOrFail();
    expect($reprintJob->id)->not->toBe($originalJob->id)
        ->and($reprintJob->status->value)->toBe('queued')
        ->and($reprintJob->artifact)->toBeNull();

    // Switching from web session auth (admin, above) to a Sanctum bearer
    // token — the guard must be forgotten or it can keep resolving the
    // previous request's identity (see EnsureProductionDeviceToken tests
    // for the same gotcha).
    $this->app['auth']->forgetGuards();

    $claim = $this->withToken($token)->postJson("/api/v1/production/jobs/{$reprintJob->id}/claim");
    $claim->assertOk();

    $reprintArtifact = $reprintJob->fresh()->artifact;
    expect($reprintArtifact)->not->toBeNull()
        ->and($reprintArtifact->plate_template_version_id)->toBe($plate->plate_template_version_id)
        ->and($claim->json('data.plate.legacy_code'))->toBe($plate->legacyCode->code);
});

test('admin can batch-export a small set of plates as a single zip with a manifest', function () {
    $plateA = makeReadyPlate($this->version);
    $plateB = makeReadyPlate($this->version);

    $response = $this->actingAs($this->admin)->post(route('admin.plates.export-batch'), [
        'plate_ids' => [$plateA->id, $plateB->id],
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/zip');

    $tmp = tempnam(sys_get_temp_dir(), 'batch-test-');
    file_put_contents($tmp, $response->getContent());
    $zip = new ZipArchive;
    $zip->open($tmp);

    $prefixA = PlateFilename::batchPrefix($plateA->athlete_name, $plateA->bib_number, $plateA->serial_number);
    $prefixB = PlateFilename::batchPrefix($plateB->athlete_name, $plateB->bib_number, $plateB->serial_number);

    expect($zip->locateName("{$prefixA}_FRONT.svg"))->not->toBeFalse()
        ->and($zip->locateName("{$prefixB}_FRONT.svg"))->not->toBeFalse()
        ->and($zip->locateName('manifest.json'))->not->toBeFalse();

    $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
    expect($manifest)->toHaveCount(2);

    $zip->close();
    unlink($tmp);
});

test('batch export is capped at the configured limit', function () {
    config(['plate-studio.batch_export_limit' => 2]);
    $ids = [
        makeReadyPlate($this->version)->id,
        makeReadyPlate($this->version)->id,
        makeReadyPlate($this->version)->id,
    ];

    $this->actingAs($this->admin)->post(route('admin.plates.export-batch'), ['plate_ids' => $ids])
        ->assertSessionHasErrors('plate_ids');
});
