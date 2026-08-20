<?php

use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\PlateTemplateVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->operator = User::factory()->create();
    $this->operator->assignRole('event_operator');
});

test('the participant page shows NO_RESULT when there is nothing to produce from', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);

    $response = $this->actingAs($this->operator)->get(route('operator.participants.show', $participant));

    $response->assertInertia(fn ($page) => $page
        ->where('eligibility.eligible', false)
        ->where('eligibility.reasons', fn ($reasons) => collect($reasons)->contains('NO_RESULT'))
    );
});

test('the participant page marks eligible once a result and a template both exist', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    EventResult::factory()->create(['event_participant_id' => $participant->id, 'official_time' => '03:00:00']);
    $version = PlateTemplateVersion::factory()->create(['status' => PlateTemplateVersionStatus::Published]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $response = $this->actingAs($this->operator)->get(route('operator.participants.show', $participant));

    $response->assertInertia(fn ($page) => $page->where('eligibility.eligible', true));
});

test('a manual override on the result surfaces as a badge flag on the participant payload', function () {
    $edition = EventEdition::factory()->create();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    EventResult::factory()->create([
        'event_participant_id' => $participant->id,
        'official_time' => '03:00:00',
        'manual_override_at' => now(),
        'manual_override_fields' => ['official_time'],
    ]);

    $response = $this->actingAs($this->operator)->get(route('operator.participants.show', $participant));

    $response->assertInertia(fn ($page) => $page->where('participant.manual_override', true));
});
