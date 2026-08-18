<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\PlateStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Enums\ResultStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventPlateTemplate;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\LegacyCode;
use App\Models\Medal;
use App\Models\Plate;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->operator = User::factory()->create();
    $this->operator->assignRole('event_operator');
});

function e2eEdition(): EventEdition
{
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);

    return $edition->fresh();
}

test('template versioning: a plate keeps referencing the version it was printed with even after the event default moves to a newer version', function () {
    $edition = e2eEdition();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $template = PlateTemplate::factory()->create();

    $v1 = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'version' => 1,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 40, 'height_mm' => 6, 'font_size_pt' => 8],
        ]],
    ]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $v1->id, 'is_default' => true, 'active' => true]);

    $participantA = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);
    $this->actingAs($this->operator)->post(route('operator.participants.plate', $participantA));
    $plateA = Plate::where('event_participant_id', $participantA->id)->firstOrFail();
    expect($plateA->plate_template_version_id)->toBe($v1->id);

    // Admin publishes V2 and makes it the event's new default.
    $v2 = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'version' => 2,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => $v1->front_configuration,
    ]);
    $this->actingAs($this->admin)->post(route('admin.editions.production-setup.assign-template', $edition), [
        'plate_template_version_id' => $v2->id,
    ]);

    $participantB = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    $this->actingAs($this->operator)->post(route('operator.participants.plate', $participantB));
    $plateB = Plate::where('event_participant_id', $participantB->id)->firstOrFail();

    expect($plateB->plate_template_version_id)->toBe($v2->id)
        ->and($plateA->fresh()->plate_template_version_id)->toBe($v1->id);
});

test('full integrated lifecycle: template to legacy profile', function () {
    // 1. Admin designs and publishes a molde.
    $this->actingAs($this->admin)->post(route('admin.plate-studio.templates.store'), [
        'name' => 'Cozumel E2E', 'width_mm' => 60, 'height_mm' => 40, 'orientation' => 'landscape', 'safe_margin_mm' => 3,
    ]);
    $template = PlateTemplate::where('name', 'Cozumel E2E')->firstOrFail();
    $version = $template->versions()->firstOrFail();

    $this->actingAs($this->admin)->patch(route('admin.plate-studio.versions.update', $version), [
        'front_configuration' => ['elements' => [
            ['id' => 'name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 40, 'height_mm' => 6, 'font_size_pt' => 8],
            ['id' => 'qr', 'type' => 'qr', 'x_mm' => 40, 'y_mm' => 20, 'width_mm' => 12, 'height_mm' => 12],
        ]],
        'back_configuration' => ['elements' => [
            ['id' => 'serial', 'type' => 'serial', 'text' => '{{plate_serial}}', 'x_mm' => 3, 'y_mm' => 30, 'width_mm' => 30, 'height_mm' => 4],
        ]],
    ]);
    $this->actingAs($this->admin)->post(route('admin.plate-studio.versions.publish', $version));
    expect($version->fresh()->status)->toBe(PlateTemplateVersionStatus::Published);

    // 2. Assigned to the event edition.
    $edition = e2eEdition();
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);
    $this->actingAs($this->admin)->post(route('admin.editions.production-setup.assign-template', $edition), [
        'plate_template_version_id' => $version->id,
    ]);

    // 3. Participant + result exist.
    $athlete = User::factory()->create();
    $participant = EventParticipant::factory()->create([
        'event_edition_id' => $edition->id,
        'event_race_id' => $race->id,
        'user_id' => $athlete->id,
        'bib_number' => '777',
        'full_name' => 'Zuriel Ávila',
    ]);
    EventResult::factory()->create(['event_participant_id' => $participant->id, 'official_time' => '05:21:18', 'status' => ResultStatus::Finished]);

    // 4. Operator searches, previews (using V1), and generates.
    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);
    $preview = $this->actingAs($this->operator)->postJson(route('operator.preview'), [
        'event_participant_id' => $participant->id, 'face' => 'front', 'mode' => 'product',
    ]);
    $preview->assertOk();
    expect($preview->json('svg'))->toContain('Zuriel Ávila');

    $this->actingAs($this->operator)->post(route('operator.participants.plate', $participant));
    $plate = Plate::where('event_participant_id', $participant->id)->firstOrFail();
    expect($plate->plate_template_version_id)->toBe($version->id)
        ->and($plate->status)->toBe(PlateStatus::Queued);

    $legacyCode = $plate->legacyCode;
    expect($legacyCode)->not->toBeNull()->and($legacyCode->user_id)->toBe($athlete->id);

    // 5. Real QR + SVG front/back downloads.
    $this->get(route('legacy-code.qr', $legacyCode->code))->assertOk();
    $front = $this->actingAs($this->operator)->get(route('admin.plates.export', [$plate, 'front', 'svg']));
    $front->assertOk();
    $back = $this->actingAs($this->operator)->get(route('admin.plates.export', [$plate, 'back', 'svg']));
    $back->assertOk();
    expect($back->getContent())->toContain($plate->serial_number);

    // 6. Production: queued -> processing -> ready -> delivered.
    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'processing']);
    foreach (['front', 'back', 'qr'] as $item) {
        $this->actingAs($this->admin)->patch(route('production.plates.checklist', $plate), ['item' => $item, 'checked' => true]);
    }
    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'ready']);
    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'delivered']);
    expect($plate->fresh()->status)->toBe(PlateStatus::Delivered);

    // 7. Legacy profile already resolves (athlete was known up front).
    $this->get(route('legacy-code.show', $legacyCode->code))->assertOk();
});

test('full quick-plate lifecycle: no database record to legacy id via claim', function () {
    $edition = e2eEdition();
    $template = PlateTemplate::factory()->create();
    $version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
        'front_configuration' => ['elements' => [
            ['id' => 'name', 'type' => 'dynamic_text', 'field' => 'athlete_name', 'x_mm' => 3, 'y_mm' => 3, 'width_mm' => 40, 'height_mm' => 6, 'font_size_pt' => 8],
        ]],
    ]);
    EventPlateTemplate::create(['event_edition_id' => $edition->id, 'plate_template_version_id' => $version->id, 'is_default' => true, 'active' => true]);

    $this->actingAs($this->operator)->post(route('operator.select-event'), ['event_edition_id' => $edition->id]);

    $preview = $this->actingAs($this->operator)->postJson(route('operator.preview'), [
        'quick' => ['athlete_name' => 'Corredor Sin Cuenta'], 'face' => 'front', 'mode' => 'product',
    ]);
    $preview->assertOk();
    expect($preview->json('svg'))->toContain('Corredor Sin Cuenta')
        ->and(LegacyCode::count())->toBe(0);

    $response = $this->actingAs($this->operator)->post(route('operator.quick-plate'), ['athlete_name' => 'Corredor Sin Cuenta']);
    $plate = Plate::where('athlete_name', 'Corredor Sin Cuenta')->firstOrFail();
    $response->assertRedirect(route('operator.quick-plate.show', $plate));

    expect($plate->user_id)->toBeNull()
        ->and($plate->plate_template_version_id)->toBe($version->id);

    $legacyCode = $plate->legacyCode;
    expect($legacyCode->user_id)->toBeNull();

    $noRole = User::factory()->create();
    $this->actingAs($noRole)->get(route('admin.plates.export', [$plate, 'front', 'svg']))->assertForbidden();
    $this->actingAs($this->operator)->get(route('admin.plates.export', [$plate, 'front', 'svg']))->assertOk();

    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'processing']);
    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'ready']);
    $this->actingAs($this->admin)->patch(route('production.plates.status', $plate), ['status' => 'delivered']);

    // Weeks later, someone scans the QR and claims it.
    $claimant = User::factory()->create();
    $this->actingAs($claimant)->post(route('legacy-code.claim', $legacyCode->code))->assertRedirect();

    expect($legacyCode->fresh()->user_id)->toBe($claimant->id)
        ->and($plate->fresh()->user_id)->toBe($claimant->id)
        ->and(Medal::where('user_id', $claimant->id)->exists())->toBeTrue();

    // The claimant can now see the plate resolve into their own account via the
    // same permanent URL that was engraved on the physical plate before anyone
    // owned it.
    $this->get(route('legacy-code.show', $legacyCode->code))->assertOk();
});
