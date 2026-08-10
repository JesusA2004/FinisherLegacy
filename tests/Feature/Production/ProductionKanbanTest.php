<?php

use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\ProductionJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->productionUser = User::factory()->create();
    $this->productionUser->assignRole('production_operator');
});

function plateWithJob(PlateStatus $status): Plate
{
    $plate = Plate::factory()->create([
        'generation_mode' => PlateGenerationMode::Quick,
        'status' => $status,
    ]);
    $legacyCode = LegacyCode::factory()->create(['plate_id' => $plate->id]);
    $plate->update(['legacy_code_id' => $legacyCode->id]);
    ProductionJob::factory()->create(['plate_id' => $plate->id, 'status' => 'queued']);

    return $plate->fresh();
}

test('production routes are blocked without the production.access permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('production.index'))->assertForbidden();
});

test('the kanban groups plates into the right columns', function () {
    plateWithJob(PlateStatus::Queued);
    plateWithJob(PlateStatus::Processing);
    plateWithJob(PlateStatus::Ready);
    plateWithJob(PlateStatus::Delivered);

    $response = $this->actingAs($this->productionUser)->get(route('production.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('production/Index')
        ->has('columns.pending', 1)
        ->has('columns.processing', 1)
        ->has('columns.ready', 1)
        ->has('columns.delivered', 1)
    );
});

test('a plate can advance from queued to processing to ready to delivered', function () {
    $plate = plateWithJob(PlateStatus::Queued);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'processing'])
        ->assertRedirect();
    expect($plate->fresh()->status)->toBe(PlateStatus::Processing);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'ready'])
        ->assertRedirect();
    expect($plate->fresh()->status)->toBe(PlateStatus::Ready);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'delivered'])
        ->assertRedirect();
    expect($plate->fresh()->status)->toBe(PlateStatus::Delivered)
        ->and($plate->fresh()->delivered_at)->not->toBeNull();

    $job = $plate->fresh()->latestProductionJob;
    expect($job->status->value)->toBe('completed');
});

test('a plate cannot skip straight from queued to delivered', function () {
    $plate = plateWithJob(PlateStatus::Queued);

    $response = $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'delivered']);

    $response->assertSessionHasErrors('status');
    expect($plate->fresh()->status)->toBe(PlateStatus::Queued);
});

test('a delivered plate cannot move backwards', function () {
    $plate = plateWithJob(PlateStatus::Delivered);

    $response = $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'processing']);

    $response->assertSessionHasErrors('status');
});

test('production.view alone cannot mutate plate status', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['production.access', 'production.view']);
    $plate = plateWithJob(PlateStatus::Queued);

    $this->actingAs($viewer)
        ->patch(route('production.plates.status', $plate), ['status' => 'processing'])
        ->assertForbidden();
});
