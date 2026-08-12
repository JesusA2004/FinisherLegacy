<?php

use App\Enums\LegacyCodeStatus;
use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
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
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 40, 'y_mm' => 20, 'width_mm' => 12, 'height_mm' => 12],
        ]],
        'back_configuration' => ['elements' => [
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 30, 'width_mm' => 30, 'height_mm' => 4],
        ]],
    ]);

    $this->plate = Plate::factory()->create([
        'plate_template_id' => $template->id,
        'plate_template_version_id' => $this->version->id,
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => PlateStatus::Queued,
    ]);

    $this->legacyCode = LegacyCode::create([
        'code' => CodeGenerator::unique('FL', fn ($c) => LegacyCode::where('code', $c)->exists()),
        'uuid' => Str::uuid(),
        'plate_id' => $this->plate->id,
        'status' => LegacyCodeStatus::Assigned,
        'assigned_at' => now(),
    ]);
    $this->plate->update(['legacy_code_id' => $this->legacyCode->id]);
});

test('athletes cannot download production files', function () {
    $athlete = User::factory()->create();
    $athlete->assignRole('athlete');

    $this->actingAs($athlete)->get(route('admin.plates.export', [$this->plate, 'front', 'svg']))->assertForbidden();
});

test('admin can download front svg with correct dimensions', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.plates.export', [$this->plate, 'front', 'svg']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');
    expect($response->getContent())->toContain('width="60mm"')->toContain('height="40mm"');
});

test('admin can download front png at 300dpi with exact pixel dimensions', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.plates.export', [$this->plate, 'front', 'png']).'?dpi=300');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');

    $image = imagecreatefromstring($response->getContent());
    expect(imagesx($image))->toBe((int) round(60 / 25.4 * 300))
        ->and(imagesy($image))->toBe((int) round(40 / 25.4 * 300));
});

test('admin can download front pdf sized to the exact physical plate dimensions', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.plates.export', [$this->plate, 'front', 'pdf']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('admin can download the qr and the full export package with metadata', function () {
    $qr = $this->actingAs($this->admin)->get(route('admin.plates.export-qr', $this->plate));
    $qr->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

    $package = $this->actingAs($this->admin)->get(route('admin.plates.export-package', $this->plate));
    $package->assertOk()->assertHeader('Content-Type', 'application/zip');

    $tmp = tempnam(sys_get_temp_dir(), 'zip-test-');
    file_put_contents($tmp, $package->getContent());
    $zip = new ZipArchive;
    $zip->open($tmp);

    expect($zip->locateName('front.svg'))->not->toBeFalse()
        ->and($zip->locateName('back.svg'))->not->toBeFalse()
        ->and($zip->locateName('qr.svg'))->not->toBeFalse()
        ->and($zip->locateName('metadata.json'))->not->toBeFalse();

    $metadata = json_decode((string) $zip->getFromName('metadata.json'), true);
    expect($metadata['serial'])->toBe($this->plate->serial_number)
        ->and($metadata['legacy_code'])->toBe($this->legacyCode->code)
        ->and($metadata['template_version'])->toBe($this->version->version);

    $zip->close();
    unlink($tmp);
});

test('regenerating files never creates a new legacy code', function () {
    $originalCode = $this->legacyCode->code;

    $this->actingAs($this->admin)->get(route('admin.plates.export', [$this->plate, 'front', 'svg']));
    $this->actingAs($this->admin)->get(route('admin.plates.export', [$this->plate, 'front', 'svg']));

    expect(LegacyCode::where('plate_id', $this->plate->id)->count())->toBe(1)
        ->and($this->legacyCode->fresh()->code)->toBe($originalCode);
});

test('a plate without a template version cannot be exported', function () {
    $bare = Plate::factory()->create(['plate_template_version_id' => null]);

    $this->actingAs($this->admin)->get(route('admin.plates.export', [$bare, 'front', 'svg']))->assertStatus(422);
});
