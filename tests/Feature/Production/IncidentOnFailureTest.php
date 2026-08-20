<?php

use App\Enums\ProductionJobStatus;
use App\Models\EventEdition;
use App\Models\EventIncident;
use App\Models\Plate;
use App\Models\ProductionJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('failing a production job automatically opens an incident', function () {
    $this->seed(RolePermissionSeeder::class);
    $operator = User::factory()->create();
    $operator->assignRole('production_operator');

    $edition = EventEdition::factory()->create();
    $plate = Plate::factory()->create(['event_edition_id' => $edition->id]);
    $job = ProductionJob::factory()->create([
        'plate_id' => $plate->id,
        'event_edition_id' => $plate->event_edition_id,
        'status' => ProductionJobStatus::EngravingFront,
        'front_started_at' => now(),
    ]);

    $response = $this->actingAs($operator)->post(route('production.jobs.fail', $job), [
        'error_code' => 'engraving_failed',
        'message' => 'Falla simulada.',
    ]);

    $response->assertRedirect();
    expect($job->fresh()->status)->toBe(ProductionJobStatus::Failed);

    $incident = EventIncident::where('plate_id', $plate->id)->first();
    expect($incident)->not->toBeNull()
        ->and($incident->type->value)->toBe('print_failure')
        ->and($incident->status->value)->toBe('open');
});
