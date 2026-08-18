<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\PlateTemplateVersion;
use App\Services\PlateGenerationService;
use App\Services\PlateTemplateRenderService;
use App\Support\PlateRenderData;
use Database\Seeders\RolePermissionSeeder;
use Symfony\Component\Finder\Finder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function editionWithTemplate(): array
{
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);

    $version = PlateTemplateVersion::factory()->create([
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'distance', 'type' => 'dynamic_text', 'field' => 'distance', 'visible_when' => 'distance', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 30, 'height_mm' => 5, 'font_size_pt' => 8],
            ['id' => 'swim', 'type' => 'static_text', 'text' => 'SWIM {{swim_time}}', 'visible_when' => 'swim_time', 'x_mm' => 3, 'y_mm' => 10, 'width_mm' => 20, 'height_mm' => 4, 'font_size_pt' => 6],
            ['id' => 'bike', 'type' => 'static_text', 'text' => 'BIKE {{bike_time}}', 'visible_when' => 'bike_time', 'x_mm' => 25, 'y_mm' => 10, 'width_mm' => 20, 'height_mm' => 4, 'font_size_pt' => 6],
            ['id' => 'run', 'type' => 'static_text', 'text' => 'RUN {{run_time}}', 'visible_when' => 'run_time', 'x_mm' => 3, 'y_mm' => 16, 'width_mm' => 20, 'height_mm' => 4, 'font_size_pt' => 6],
        ]],
        'back_configuration' => ['elements' => [
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 20, 'y_mm' => 10, 'width_mm' => 15, 'height_mm' => 15],
        ]],
    ]);

    EventPlateTemplate::create([
        'event_edition_id' => $edition->id,
        'plate_template_version_id' => $version->id,
        'is_default' => true,
        'active' => true,
    ]);

    return [$edition->fresh(), $version];
}

test('integrated running: distance is auto-formatted from the race, no swim/bike/run leaks into the snapshot', function () {
    [$edition, $version] = editionWithTemplate();
    $race = EventRace::factory()->create([
        'event_edition_id' => $edition->id,
        'name' => '42K',
        'distance_value' => 42.195,
        'distance_unit' => 'km',
    ]);
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'category' => '30-39',
    ]);
    EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '03:47:21',
        'pace' => '5:23',
        'overall_position' => 142,
        'category_position' => 18,
    ]);

    $plate = app(PlateGenerationService::class)->generateIntegrated($participant->fresh(['eventRace', 'result']));

    expect($plate->dynamic_fields['distance'])->toBe('42.195 km')
        ->and($plate->dynamic_fields['swim_time'])->toBeNull()
        ->and($plate->dynamic_fields['bike_time'])->toBeNull()
        ->and($plate->dynamic_fields['run_time'])->toBeNull()
        ->and($plate->dynamic_fields['overall_position'])->toBe(142)
        ->and($plate->dynamic_fields['category'])->toBe('30-39')
        ->and($plate->dynamic_fields['category_position'])->toBe(18);

    $svg = app(PlateTemplateRenderService::class)
        ->renderSvg($version, 'front', PlateRenderData::fromPlate($plate), 'product');

    expect($svg)->toContain('42.195 km')
        ->not->toContain('SWIM')
        ->not->toContain('BIKE')
        ->not->toContain('RUN ');
});

test('integrated triathlon: swim/bike/run splits freeze correctly onto the plate snapshot and render on both faces', function () {
    [$edition, $version] = editionWithTemplate();
    $race = EventRace::factory()->create([
        'event_edition_id' => $edition->id,
        'name' => '70.3',
        'distance_value' => 70.3,
        'distance_unit' => 'mi',
    ]);
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
    ]);
    $result = EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '05:21:18',
    ]);
    $result->splits()->createMany([
        ['type' => 'swim', 'label' => 'Swim', 'sequence' => 1, 'segment_time' => '42:15'],
        ['type' => 'bike', 'label' => 'Bike', 'sequence' => 2, 'segment_time' => '2:49:33'],
        ['type' => 'run', 'label' => 'Run', 'sequence' => 3, 'segment_time' => '1:44:51'],
    ]);

    $plate = app(PlateGenerationService::class)->generateIntegrated($participant->fresh(['eventRace', 'result.splits']));

    expect($plate->dynamic_fields['swim_time'])->toBe('42:15')
        ->and($plate->dynamic_fields['bike_time'])->toBe('2:49:33')
        ->and($plate->dynamic_fields['run_time'])->toBe('1:44:51');

    $renderer = app(PlateTemplateRenderService::class);
    $data = PlateRenderData::fromPlate($plate);

    $front = $renderer->renderSvg($version, 'front', $data, 'product');
    expect($front)->toContain('SWIM 42:15')
        ->toContain('BIKE 2:49:33')
        ->toContain('RUN 1:44:51');

    $back = $renderer->renderSvg($version, 'back', $data, 'product');
    expect($back)->toContain('<svg'); // the nested QR wrapper renders at all
});

test('invariant: Plate is only ever created through PlateGenerationService in application code, not seeders/controllers', function () {
    $offenders = [];

    foreach ((new Finder)->in([app_path(), database_path('seeders')])->name('*.php')->files() as $file) {
        if (str_ends_with($file->getFilename(), 'PlateGenerationService.php')) {
            continue;
        }

        // Strip comments/docblocks first so this doesn't false-positive on
        // prose that merely mentions "Plate::create(" (like this very file
        // used to, in a comment explaining that it does NOT do that).
        $codeOnly = '';
        foreach (token_get_all('<?php '.$file->getContents()) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $codeOnly .= is_array($token) ? $token[1] : $token;
        }

        if (preg_match('/\bPlate::create\s*\(/', $codeOnly)) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
