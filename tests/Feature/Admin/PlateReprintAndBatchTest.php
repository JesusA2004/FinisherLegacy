<?php

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ProductionJobStatus;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionJob;
use App\Models\User;
use App\Support\CodeGenerator;
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
        'status' => ProductionJobStatus::Completed,
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

    expect($zip->locateName("{$plateA->serial_number}-front.svg"))->not->toBeFalse()
        ->and($zip->locateName("{$plateB->serial_number}-front.svg"))->not->toBeFalse()
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
