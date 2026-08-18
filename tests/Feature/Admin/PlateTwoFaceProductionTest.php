<?php

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventPlateTemplate;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\User;
use App\Services\PlateExportService;
use App\Services\PlateGenerationService;
use App\Services\PlateTemplateRenderService;
use App\Support\CodeGenerator;
use App\Support\PlateRenderData;
use Database\Seeders\PlateTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(PlateTemplateSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function plateForPreset(string $slug): Plate
{
    $version = PlateTemplateVersion::query()
        ->whereHas('plateTemplate', fn ($q) => $q->where('slug', $slug))
        ->firstOrFail();

    $plate = Plate::factory()->create([
        'plate_template_id' => $version->plate_template_id,
        'plate_template_version_id' => $version->id,
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => PlateStatus::Queued,
    ]);

    $legacyCode = LegacyCode::create([
        'code' => CodeGenerator::unique('FL', fn ($c) => LegacyCode::where('code', $c)->exists()),
        'uuid' => Str::uuid(),
        'plate_id' => $plate->id,
        'status' => LegacyCodeStatus::Assigned,
        'assigned_at' => now(),
    ]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);

    return $plate->fresh(['legacyCode', 'plateTemplateVersion.plateTemplate']);
}

test('the running preset front has no QR and the back does', function () {
    $version = PlateTemplateVersion::query()->whereHas('plateTemplate', fn ($q) => $q->where('slug', 'running-classic-60x40'))->firstOrFail();

    $frontTypes = collect($version->front_configuration['elements'])->pluck('type');
    $backTypes = collect($version->back_configuration['elements'])->pluck('type');

    expect($frontTypes)->not->toContain('qr')
        ->and($backTypes)->toContain('qr');
});

test('the triathlon preset front has no QR and the back does', function () {
    $version = PlateTemplateVersion::query()->whereHas('plateTemplate', fn ($q) => $q->where('slug', 'triathlon-premium-60x40'))->firstOrFail();

    $frontTypes = collect($version->front_configuration['elements'])->pluck('type');
    $backTypes = collect($version->back_configuration['elements'])->pluck('type');

    expect($frontTypes)->not->toContain('qr')
        ->and($backTypes)->toContain('qr');
});

test('the cycling preset front has no QR and the back does', function () {
    $version = PlateTemplateVersion::query()->whereHas('plateTemplate', fn ($q) => $q->where('slug', 'cycling-classic-60x40'))->firstOrFail();

    $frontTypes = collect($version->front_configuration['elements'])->pluck('type');
    $backTypes = collect($version->back_configuration['elements'])->pluck('type');

    expect($frontTypes)->not->toContain('qr')
        ->and($backTypes)->toContain('qr');
});

test('front export does not contain a QR, back export does, for the same plate', function () {
    $plate = plateForPreset('running-classic-60x40');

    $front = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'front', 'svg']));
    $back = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'back', 'svg']));

    $front->assertOk();
    $back->assertOk();

    // The QR renderer draws a nested <svg> wrapper for the qr element only —
    // absent on front, present on back.
    $frontSvgCount = substr_count($front->getContent(), '<svg');
    $backSvgCount = substr_count($back->getContent(), '<svg');

    expect($frontSvgCount)->toBe(1) // just the root <svg>, no nested QR
        ->and($backSvgCount)->toBeGreaterThan(1); // root + nested QR wrapper
});

test('front and back exports of the same plate share the Legacy Code and template version', function () {
    $plate = plateForPreset('triathlon-premium-60x40');

    $front = $this->actingAs($this->admin)->getJson(route('admin.plates.export', [$plate, 'front', 'svg']).'?mode=product');
    $back = $this->actingAs($this->admin)->getJson(route('admin.plates.export', [$plate, 'back', 'svg']).'?mode=product');

    $front->assertOk();
    $back->assertOk();
    expect($back->getContent())->toContain($plate->legacyCode->code);

    $freshFront = $plate->fresh();
    expect($freshFront->plate_template_version_id)->toBe($plate->plate_template_version_id);
});

test('the export package zip contains both faces in every format plus production.json', function () {
    $plate = plateForPreset('cycling-classic-60x40');

    $response = $this->actingAs($this->admin)->get(route('admin.plates.export-package', $plate));
    $response->assertOk()->assertHeader('Content-Type', 'application/zip');

    $tmp = tempnam(sys_get_temp_dir(), 'zip-package-test-');
    file_put_contents($tmp, $response->getContent());
    $zip = new ZipArchive;
    $zip->open($tmp);

    $root = 'plate-'.$plate->serial_number.'/';
    foreach (['front.svg', 'front.png', 'front.pdf', 'back.svg', 'back.png', 'back.pdf', 'qr.svg', 'production.json'] as $file) {
        expect($zip->locateName($root.$file))->not->toBeFalse("Missing {$file} in package");
    }

    $production = json_decode($zip->getFromName($root.'production.json'), true);
    expect($production['serial'])->toBe($plate->serial_number)
        ->and($production['legacy_code'])->toBe($plate->legacyCode->code)
        ->and($production['template_version'])->toBe($plate->plateTemplateVersion->version)
        ->and((float) $production['width_mm'])->toBe(60.0)
        ->and((float) $production['height_mm'])->toBe(40.0)
        ->and($production['faces'])->toBe(['front', 'back']);

    $zip->close();
    unlink($tmp);
});

test('the batch export zip uses sanitized bib_name_face filenames with no email', function () {
    $plate = plateForPreset('running-classic-60x40');
    $plate->update(['athlete_name' => 'María Cármen Alániz', 'bib_number' => '1242']);

    $svc = app(PlateExportService::class);
    $content = $svc->exportBatch(Plate::whereKey($plate->id)->get());

    $tmp = tempnam(sys_get_temp_dir(), 'zip-batch-test-');
    file_put_contents($tmp, $content);
    $zip = new ZipArchive;
    $zip->open($tmp);

    expect($zip->locateName('1242_MARIA-CARMEN-ALANIZ_FRONT.svg'))->not->toBeFalse()
        ->and($zip->locateName('1242_MARIA-CARMEN-ALANIZ_BACK.svg'))->not->toBeFalse();

    for ($i = 0; $i < $zip->numFiles; $i++) {
        expect($zip->getNameIndex($i))->not->toContain('@');
    }

    $zip->close();
    unlink($tmp);
});

test('front.svg and back.svg share the same physical plate dimensions', function () {
    $plate = plateForPreset('triathlon-premium-60x40');

    $front = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'front', 'svg']));
    $back = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'back', 'svg']));

    expect($front->getContent())->toContain('width="60mm"')->toContain('height="40mm"')
        ->and($back->getContent())->toContain('width="60mm"')->toContain('height="40mm"');
});

test('the back QR resolves to the same permanent legacy-code URL as the plate\'s code', function () {
    $plate = plateForPreset('cycling-classic-60x40');
    $renderer = app(PlateTemplateRenderService::class);

    $resolved = $renderer->resolveElements($plate->plateTemplateVersion, 'back', PlateRenderData::fromPlate($plate));
    $qrElement = collect($resolved['elements'])->firstWhere('type', 'qr');

    expect($qrElement['qr_content'])->toBe(url('/l/'.$plate->legacyCode->code));
});

test('quick plate generation produces a plate whose front and back both export, with a back QR', function () {
    $edition = EventEdition::factory()->create();
    $version = PlateTemplateVersion::query()->whereHas('plateTemplate', fn ($q) => $q->where('slug', 'running-classic-60x40'))->firstOrFail();
    EventPlateTemplate::create([
        'event_edition_id' => $edition->id,
        'plate_template_version_id' => $version->id,
        'is_default' => true,
        'active' => true,
    ]);

    $plate = app(PlateGenerationService::class)->generateQuick($edition, ['athlete_name' => 'Prueba Quick']);

    expect($plate->plate_template_version_id)->toBe($version->id)
        ->and($plate->legacyCode)->not->toBeNull()
        ->and($plate->user_id)->toBeNull();

    $front = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'front', 'svg']));
    $back = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'back', 'svg']));
    $front->assertOk();
    $back->assertOk();
    expect($back->getContent())->toContain($plate->legacyCode->code);
});

test('reprint preserves the legacy code and both faces still render from the original template version', function () {
    $plate = plateForPreset('triathlon-premium-60x40');
    $plate->update(['status' => PlateStatus::Delivered]);
    $originalCode = $plate->legacyCode->code;
    $originalVersionId = $plate->plate_template_version_id;

    $this->actingAs($this->admin)->post(route('admin.plates.reprint', $plate), [
        'reason' => 'Placa dañada en tránsito',
        'use_original' => true,
    ])->assertRedirect();

    $plate->refresh();
    expect($plate->legacyCode->code)->toBe($originalCode)
        ->and($plate->plate_template_version_id)->toBe($originalVersionId);

    $front = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'front', 'svg']));
    $back = $this->actingAs($this->admin)->get(route('admin.plates.export', [$plate, 'back', 'svg']));
    $front->assertOk();
    $back->assertOk();
    expect($back->getContent())->toContain($originalCode);
});

test('publishing a version is blocked when the back QR is smaller than the template\'s validated minimum', function () {
    $manager = User::factory()->create();
    $manager->assignRole('admin');

    $template = PlateTemplate::factory()->create(['minimum_validated_qr_size_mm' => 12]);
    $version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Draft,
        'back_configuration' => ['elements' => [
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 20, 'y_mm' => 10, 'width_mm' => 8, 'height_mm' => 8],
        ]],
    ]);

    $this->actingAs($manager)
        ->post(route('admin.plate-studio.versions.publish', $version))
        ->assertStatus(422);

    expect($version->fresh()->status)->toBe(PlateTemplateVersionStatus::Draft);
});
