<?php

use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\User;
use App\Services\CalibrationCardService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('front calibration card is a fixed 60x40mm svg with a legibility and line-weight ladder', function () {
    $svg = app(CalibrationCardService::class)->renderFront();

    expect($svg)->toContain('width="60mm"')
        ->toContain('height="40mm"')
        ->toContain('CALIBRACIÓN')
        ->toContain('2pt')
        ->toContain('4pt');

    expect(substr_count($svg, '<line'))->toBe(3);
});

test('back calibration card exposes four qr sizes and no real legacy code', function () {
    $svg = app(CalibrationCardService::class)->renderBack();

    expect($svg)->toContain('width="60mm"')
        ->toContain('height="40mm"')
        ->toContain('8mm')
        ->toContain('10mm')
        ->toContain('12mm')
        ->toContain('14mm')
        ->toContain('NO ES UN LEGACY CODE REAL')
        ->not->toContain('/l/');

    expect(LegacyCode::query()->count())->toBe(0);
});

test('admin can download both calibration card faces as svg attachments', function () {
    $front = $this->actingAs($this->admin)->get('/admin/plate-studio/calibration/front');
    $front->assertOk();
    $front->assertHeader('Content-Type', 'image/svg+xml');
    $front->assertHeader('Content-Disposition', 'attachment; filename="calibracion-laser-front.svg"');

    $back = $this->actingAs($this->admin)->get('/admin/plate-studio/calibration/back');
    $back->assertOk();
    $back->assertHeader('Content-Disposition', 'attachment; filename="calibracion-laser-back.svg"');
});

test('an invalid calibration face is rejected', function () {
    $this->actingAs($this->admin)->get('/admin/plate-studio/calibration/side')->assertNotFound();
});

test('generating a calibration card never creates a plate or legacy code', function () {
    app(CalibrationCardService::class)->renderFront();
    app(CalibrationCardService::class)->renderBack();

    expect(Plate::query()->count())->toBe(0)
        ->and(LegacyCode::query()->count())->toBe(0);
});
