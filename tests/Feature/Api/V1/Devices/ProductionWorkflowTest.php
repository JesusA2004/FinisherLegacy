<?php

use App\Enums\DeviceAbility;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionDevice;
use App\Models\ProductionJob;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function workflowJob(): ProductionJob
{
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

    return ProductionJob::factory()->create(['plate_id' => $plate->id, 'status' => 'queued']);
}

function workflowToken(ProductionDevice $device): string
{
    return $device->createToken('test', DeviceAbility::all())->plainTextToken;
}

test('the full device workflow reaches delivered end to end', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk()->assertJsonPath('data.status', 'assigned');
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk()->assertJsonPath('data.status', 'preparing');
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk()->assertJsonPath('data.status', 'engraving_front');
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/complete")->assertOk()->assertJsonPath('data.status', 'awaiting_flip');
    $api->postJson("/api/v1/production/jobs/{$job->id}/flip/confirm")->assertOk()->assertJsonPath('data.status', 'awaiting_flip');
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/start")->assertOk()->assertJsonPath('data.status', 'engraving_back');
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/complete")->assertOk()->assertJsonPath('data.status', 'verifying_qr');

    $legacyCode = $job->plate->fresh()->legacyCode->code;
    $api->postJson("/api/v1/production/jobs/{$job->id}/qr/verify", [
        'decoded_value' => route('legacy-code.show', $legacyCode),
    ])->assertOk()->assertJsonPath('data.status', 'ready');

    $api->postJson("/api/v1/production/jobs/{$job->id}/deliver")->assertOk()->assertJsonPath('data.status', 'delivered');

    $job->refresh();
    expect($job->status->value)->toBe('delivered')
        ->and($job->front_engraved_at)->not->toBeNull()
        ->and($job->flip_confirmed_at)->not->toBeNull()
        ->and($job->back_engraved_at)->not->toBeNull()
        ->and($job->qr_verified_at)->not->toBeNull()
        ->and($job->ready_at)->not->toBeNull()
        ->and($job->delivered_at)->not->toBeNull()
        ->and($job->front_actor_type)->toBe(ProductionDevice::class)
        ->and($job->front_actor_id)->toBe($device->id)
        ->and($job->plate->fresh()->status)->toBe(PlateStatus::Delivered);
});

test('back can never start before front completes', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();

    $response = $api->postJson("/api/v1/production/jobs/{$job->id}/back/start");

    $response->assertStatus(409);
    expect($job->fresh()->status->value)->toBe('preparing');
});

test('back cannot start without a confirmed flip, even after front completes', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/complete")->assertOk();

    $response = $api->postJson("/api/v1/production/jobs/{$job->id}/back/start");

    $response->assertStatus(409);
    $response->assertJsonPath('error.code', 'FLIP_NOT_CONFIRMED');
    expect($job->fresh()->status->value)->toBe('awaiting_flip');
});

test('the job can never reach ready without a correct QR scan', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/complete")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/flip/confirm")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/start")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/complete")->assertOk();

    $response = $api->postJson("/api/v1/production/jobs/{$job->id}/qr/verify", ['decoded_value' => 'FL-COMPLETELYWRONG']);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'QR_VERIFICATION_FAILED');
    expect($job->fresh()->status->value)->toBe('verifying_qr')
        ->and($job->fresh()->qr_verified_at)->toBeNull();
});

test('a job can never be delivered directly from verifying_qr', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/complete")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/flip/confirm")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/start")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/back/complete")->assertOk();

    $response = $api->postJson("/api/v1/production/jobs/{$job->id}/deliver");

    $response->assertStatus(409);
    expect($job->fresh()->status->value)->toBe('verifying_qr');
});

test('release is allowed from assigned and preparing, but not once engraving starts', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/release")->assertOk()->assertJsonPath('data.status', 'queued');
    expect($job->fresh()->status->value)->toBe('queued')
        ->and($job->fresh()->production_device_id)->toBeNull();

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk();

    $engravingRelease = $api->postJson("/api/v1/production/jobs/{$job->id}/release");
    $engravingRelease->assertStatus(409);
    expect($job->fresh()->status->value)->toBe('engraving_front')
        ->and($job->fresh()->production_device_id)->toBe($device->id);
});

test('device B cannot advance a job claimed by device A', function () {
    $job = workflowJob();
    $deviceA = ProductionDevice::factory()->create();
    $deviceB = ProductionDevice::factory()->create();

    $this->withToken(workflowToken($deviceA))->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();

    $this->app['auth']->forgetGuards();

    $response = $this->withToken(workflowToken($deviceB))->postJson("/api/v1/production/jobs/{$job->id}/prepare");

    $response->assertStatus(403);
    $response->assertJsonPath('error.code', 'JOB_OWNED_BY_OTHER_DEVICE');
    expect($job->fresh()->status->value)->toBe('assigned');
});

test('repeating front/complete with the same Idempotency-Key does not duplicate the timestamp or the audit log', function () {
    $job = workflowJob();
    $device = ProductionDevice::factory()->create();
    $api = $this->withToken(workflowToken($device));

    $api->postJson("/api/v1/production/jobs/{$job->id}/claim")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/prepare")->assertOk();
    $api->postJson("/api/v1/production/jobs/{$job->id}/front/start")->assertOk();

    $first = $api->withHeader('Idempotency-Key', 'front-complete-1')
        ->postJson("/api/v1/production/jobs/{$job->id}/front/complete");
    $first->assertOk();
    $firstTimestamp = $job->fresh()->front_engraved_at;

    $second = $api->withHeader('Idempotency-Key', 'front-complete-1')
        ->postJson("/api/v1/production/jobs/{$job->id}/front/complete");
    $second->assertOk();
    $second->assertHeader('Idempotency-Replayed', 'true');

    expect($job->fresh()->front_engraved_at->equalTo($firstTimestamp))->toBeTrue()
        ->and($job->fresh()->status->value)->toBe('awaiting_flip');
});
