<?php

use App\Actions\Integrations\IngestEventResult;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\Plate;
use App\Models\PlateTemplateVersion;
use App\Services\PlateEligibilityService;
use App\Support\Integrations\ExternalResultData;

test('a participant with no result is not eligible', function () {
    $participant = EventParticipant::factory()->create();

    $check = app(PlateEligibilityService::class)->check($participant);

    expect($check->eligible)->toBeFalse()
        ->and($check->reasons)->toContain('NO_RESULT');
});

test('a participant with a result but no template assigned is not eligible', function () {
    $participant = EventParticipant::factory()->create();
    app(IngestEventResult::class)->handle(
        $participant,
        ExternalResultData::fromArray(['external_participant_id' => 'X', 'official_time' => '03:00:00']),
        'mock',
    );

    $check = app(PlateEligibilityService::class)->check($participant->fresh());

    expect($check->eligible)->toBeFalse()
        ->and($check->reasons)->toContain('NO_TEMPLATE');
});

test('a fully ready participant is eligible', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);

    app(IngestEventResult::class)->handle(
        $participant,
        ExternalResultData::fromArray(['external_participant_id' => 'X', 'official_time' => '03:00:00']),
        'mock',
    );

    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $check = app(PlateEligibilityService::class)->check($participant->fresh());

    expect($check->eligible)->toBeTrue()
        ->and($check->reasons)->toBe([]);
});

test('a participant that already has a Plate is not eligible again', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);

    app(IngestEventResult::class)->handle(
        $participant,
        ExternalResultData::fromArray(['external_participant_id' => 'X', 'official_time' => '03:00:00']),
        'mock',
    );

    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    Plate::factory()->create(['event_participant_id' => $participant->id, 'event_edition_id' => $edition->id]);

    $check = app(PlateEligibilityService::class)->check($participant->fresh());

    expect($check->eligible)->toBeFalse()
        ->and($check->reasons)->toContain('PLATE_ALREADY_EXISTS');
});
