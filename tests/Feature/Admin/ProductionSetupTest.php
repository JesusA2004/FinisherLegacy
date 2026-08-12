<?php

use App\Enums\EditionStatus;
use App\Enums\EventStatus;
use App\Enums\PlateTemplateVersionStatus;
use App\Models\EventEdition;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\PlateTemplate;
use App\Models\PlateTemplateVersion;
use App\Models\User;
use App\Services\PlateGenerationService;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('assigning a published template version to an edition makes it the default used for new plates', function () {
    $edition = EventEdition::factory()->create(['status' => EditionStatus::Published]);
    $edition->event()->update(['status' => EventStatus::Published]);
    $race = EventRace::factory()->create(['event_edition_id' => $edition->id]);

    $template = PlateTemplate::factory()->create();
    $version = PlateTemplateVersion::factory()->create([
        'plate_template_id' => $template->id,
        'status' => PlateTemplateVersionStatus::Published,
    ]);

    $this->actingAs($this->admin)->get(route('admin.editions.production-setup.show', $edition))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/events/ProductionSetup')
            ->where('checklist.template_assigned', false)
        );

    $this->actingAs($this->admin)->post(route('admin.editions.production-setup.assign-template', $edition), [
        'plate_template_version_id' => $version->id,
    ])->assertRedirect();

    expect($edition->fresh()->defaultPlateTemplateVersion()->id)->toBe($version->id);

    $participant = EventParticipant::factory()->create(['event_edition_id' => $edition->id, 'event_race_id' => $race->id]);
    $plate = app(PlateGenerationService::class)->generateIntegrated($participant);

    expect($plate->plate_template_version_id)->toBe($version->id);
});

test('marking the physical qr test records who and when', function () {
    $edition = EventEdition::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.editions.production-setup.qr-test', $edition), [
        'notes' => 'Probado con Redmi Note 12, lectura instantánea.',
    ])->assertRedirect();

    $check = $edition->fresh()->productionCheck;
    expect($check)->not->toBeNull()
        ->and($check->qr_tested_by)->toBe($this->admin->id)
        ->and($check->notes)->toBe('Probado con Redmi Note 12, lectura instantánea.');
});

test('roles without dashboard.admin.view cannot reach production setup, even with editions.manage', function () {
    // Matches the existing admin-access convention (see AdminAccessTest): having a
    // feature permission like editions.manage isn't enough, dashboard.admin.view
    // gates the whole /admin/* panel — event_manager doesn't have it today.
    $edition = EventEdition::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('event_manager');
    $athlete = User::factory()->create();
    $athlete->assignRole('athlete');

    $this->actingAs($manager)->get(route('admin.editions.production-setup.show', $edition))->assertForbidden();
    $this->actingAs($athlete)->get(route('admin.editions.production-setup.show', $edition))->assertForbidden();
});
