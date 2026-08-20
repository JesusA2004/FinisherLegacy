<?php

use App\Enums\DeviceAbility;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ResultStatus;
use App\Models\EventParticipant;
use App\Models\EventResult;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionArtifact;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use App\Services\PlateExportService;
use App\Support\PlateRendererVersion;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('an artifact is frozen at claim time and never changes even if the linked EventResult and template default change afterward', function () {
    $template = PlateTemplate::factory()->create(['width_mm' => 60, 'height_mm' => 40]);
    $version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'time', 'type' => 'dynamic_text', 'field' => 'official_time', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 30, 'height_mm' => 5, 'font_size_pt' => 8],
        ]],
        'back_configuration' => ['elements' => []],
    ]);

    $participant = EventParticipant::factory()->create();
    $result = EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '01:00:00',
        'status' => ResultStatus::Finished,
    ]);

    $plate = Plate::factory()->create([
        'generation_mode' => PlateGenerationMode::Integrated,
        'status' => PlateStatus::Queued,
        'event_participant_id' => $participant->id,
        'plate_template_id' => $template->id,
        'plate_template_version_id' => $version->id,
        'official_time' => '01:00:00',
    ]);
    $legacyCode = LegacyCode::factory()->create(['plate_id' => $plate->id]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);

    $job = ProductionJob::factory()->create(['plate_id' => $plate->id, 'status' => 'queued']);
    $device = ProductionDevice::factory()->create();
    $token = $device->createToken('test', DeviceAbility::all())->plainTextToken;

    $claim = $this->withToken($token)->postJson("/api/v1/production/jobs/{$job->id}/claim");
    $claim->assertOk();
    $originalFrontHash = $claim->json('data.front.sha256');

    $originalContent = $this->withToken($token)->get("/api/v1/production/jobs/{$job->id}/artifact/front")->getContent();

    // 1) Change the live EventResult — the snapshot on Plate never reads
    // this after generation, so it must not affect the frozen artifact.
    $result->update(['official_time' => '05:00:00']);

    // 2) Change something unrelated to the snapshot.
    $template->update(['name' => 'Molde renombrado después del grabado']);

    // 3) Even if the Plate row's own frozen snapshot were mutated after
    // the fact (should never happen in normal operation, but proves the
    // artifact is a stored file, not a live re-render of Plate) —
    // re-rendering it now would produce different output...
    $wouldDifferNow = app(PlateExportService::class)
        ->exportFace($plate->fresh(), 'front', 'svg', textAsPaths: true)['content'];
    $plate->update(['official_time' => '05:00:00']);
    $liveRerenderAfterMutation = app(PlateExportService::class)
        ->exportFace($plate->fresh(), 'front', 'svg', textAsPaths: true)['content'];
    expect($liveRerenderAfterMutation)->not->toBe($wouldDifferNow);

    // ...but the frozen artifact never reflects any of this.
    $refetched = $this->withToken($token)->get("/api/v1/production/jobs/{$job->id}/artifact/front");
    $refetched->assertOk();

    expect(hash('sha256', $refetched->getContent()))->toBe($originalFrontHash)
        ->and($refetched->getContent())->toBe($originalContent);
});

test('publishing a new template version afterward does not change an already-generated artifact', function () {
    $template = PlateTemplate::factory()->create(['width_mm' => 60, 'height_mm' => 40]);
    $v1 = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'version' => 1,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'label', 'type' => 'static_text', 'text' => 'V1 LABEL', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 30, 'height_mm' => 5, 'font_size_pt' => 8],
        ]],
        'back_configuration' => ['elements' => []],
    ]);

    $plate = Plate::factory()->create([
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => PlateStatus::Queued,
        'plate_template_id' => $template->id,
        'plate_template_version_id' => $v1->id,
    ]);
    $legacyCode = LegacyCode::factory()->create(['plate_id' => $plate->id]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);

    $job = ProductionJob::factory()->create(['plate_id' => $plate->id, 'status' => 'queued']);
    $device = ProductionDevice::factory()->create();
    $token = $device->createToken('test', DeviceAbility::all())->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $artifact = $job->fresh()->artifact;
    expect($artifact->plate_template_version_id)->toBe($v1->id)
        ->and($artifact->renderer_version)->toBe(PlateRendererVersion::CURRENT);

    // A new version is published and becomes the event's default — the
    // artifact already generated must keep pointing at V1, never "latest".
    PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'version' => 2,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'label', 'type' => 'static_text', 'text' => 'V2 LABEL', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 30, 'height_mm' => 5, 'font_size_pt' => 8],
        ]],
    ]);

    $content = $this->withToken($token)->get("/api/v1/production/jobs/{$job->id}/artifact/front")->getContent();
    $v1Rerender = app(PlateExportService::class)->exportFace($plate->fresh(), 'front', 'svg', textAsPaths: true)['content'];

    expect($content)->toBe($v1Rerender)
        ->and($job->fresh()->artifact->plate_template_version_id)->toBe($v1->id);
});

test('claiming twice never regenerates the artifact — same DB row, same file', function () {
    $template = PlateTemplate::factory()->create(['width_mm' => 60, 'height_mm' => 40]);
    $version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => []],
        'back_configuration' => ['elements' => []],
    ]);
    $plate = Plate::factory()->create([
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => PlateStatus::Queued,
        'plate_template_id' => $template->id,
        'plate_template_version_id' => $version->id,
    ]);
    $legacyCode = LegacyCode::factory()->create(['plate_id' => $plate->id]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);
    $job = ProductionJob::factory()->create(['plate_id' => $plate->id, 'status' => 'queued']);
    $device = ProductionDevice::factory()->create();
    $token = $device->createToken('test', DeviceAbility::all())->plainTextToken;

    $this->withToken($token)->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $firstArtifactId = $job->fresh()->artifact->id;

    $this->withToken($token)->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();

    expect(ProductionArtifact::where('production_job_id', $job->id)->count())->toBe(1)
        ->and($job->fresh()->artifact->id)->toBe($firstArtifactId);
});
