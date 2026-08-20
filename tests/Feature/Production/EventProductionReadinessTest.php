<?php

use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\PlateTemplateVersion;
use App\Models\ProductionDevice;
use App\Services\Production\EventProductionReadiness;

test('an edition with no template is not ready, and the template is the only blocking reason', function () {
    $edition = EventEdition::factory()->create();
    EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $result = app(EventProductionReadiness::class)->check($edition);

    expect($result->ready)->toBeFalse()
        ->and($result->blockingReasons)->toBe(['NO_TEMPLATE'])
        ->and($result->checks['template'])->toBeFalse();
});

test('a template assigned is enough to be ready, even without a station', function () {
    $edition = EventEdition::factory()->create();
    EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $result = app(EventProductionReadiness::class)->check($edition);

    expect($result->ready)->toBeTrue()
        ->and($result->checks['station'])->toBeFalse()
        ->and($result->blockingReasons)->toBe([]);
});

test('a station with a machine profile flips that check on', function () {
    $edition = EventEdition::factory()->create();
    ProductionDevice::factory()->create(['event_edition_id' => $edition->id, 'machine_profile_id' => null]);

    $result = app(EventProductionReadiness::class)->check($edition);
    expect($result->checks['machine_profile'])->toBeFalse();
});
