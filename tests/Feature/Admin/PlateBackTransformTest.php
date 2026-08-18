<?php

use App\Enums\PlateBackTransform;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Services\GdPlateRenderer;
use App\Services\PlateTemplateRenderService;
use App\Support\PlateRenderData;

function versionWithBackTransform(PlateBackTransform $transform): PlateTemplateVersion
{
    $template = PlateTemplate::factory()->create([
        'width_mm' => 60,
        'height_mm' => 40,
        'back_transform' => $transform->value,
    ]);

    return PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'front_label', 'type' => 'static_text', 'text' => 'FRONT', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 20, 'height_mm' => 5, 'font_size_pt' => 8],
        ]],
        'back_configuration' => ['elements' => [
            ['id' => 'back_label', 'type' => 'static_text', 'text' => 'BACK', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 20, 'height_mm' => 5, 'font_size_pt' => 8],
        ]],
    ]);
}

test('mirror_x wraps the back face in the correct horizontal-flip matrix without changing physical size', function () {
    $version = versionWithBackTransform(PlateBackTransform::MirrorX);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

    expect($svg)->toContain('<g transform="translate(60,0) scale(-1,1)">')
        ->toContain('width="60mm"')
        ->toContain('height="40mm"');
});

test('mirror_y wraps the back face in the correct vertical-flip matrix without changing physical size', function () {
    $version = versionWithBackTransform(PlateBackTransform::MirrorY);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

    expect($svg)->toContain('<g transform="translate(0,40) scale(1,-1)">')
        ->toContain('width="60mm"')
        ->toContain('height="40mm"');
});

test('rotate_180 wraps the back face in a rotation around the exact plate center', function () {
    $version = versionWithBackTransform(PlateBackTransform::Rotate180);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

    expect($svg)->toContain('<g transform="rotate(180,30,20)">')
        ->toContain('width="60mm"')
        ->toContain('height="40mm"');
});

test('back_transform never applies to the front face, only the back', function () {
    $version = versionWithBackTransform(PlateBackTransform::Rotate180);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'front', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

    expect($svg)->not->toContain('rotate(180');
});

test('back_transform never applies in product (design) mode — Plate Studio always previews the original orientation', function () {
    $version = versionWithBackTransform(PlateBackTransform::MirrorX);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCT);

    expect($svg)->not->toContain('scale(-1,1)');
});

test('none is the default and applies no transform at all', function () {
    $version = versionWithBackTransform(PlateBackTransform::None);
    $svg = app(PlateTemplateRenderService::class)->renderSvg($version, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION);

    expect($svg)->not->toContain('<g transform="translate')
        ->not->toContain('<g transform="rotate');
});

test('the PNG export applies the same back_transform and keeps identical pixel dimensions', function () {
    $normal = versionWithBackTransform(PlateBackTransform::None);
    $mirrored = versionWithBackTransform(PlateBackTransform::MirrorX);

    $gd = app(GdPlateRenderer::class);
    $normalPng = $gd->renderPng($normal, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION, 300);
    $mirroredPng = $gd->renderPng($mirrored, 'back', PlateRenderData::demo(), PlateTemplateRenderService::MODE_PRODUCTION, 300);

    $normalImg = imagecreatefromstring($normalPng);
    $mirroredImg = imagecreatefromstring($mirroredPng);

    expect(imagesx($normalImg))->toBe(imagesx($mirroredImg))
        ->and(imagesy($normalImg))->toBe(imagesy($mirroredImg))
        ->and($normalPng)->not->toBe($mirroredPng); // pixels actually differ
});
