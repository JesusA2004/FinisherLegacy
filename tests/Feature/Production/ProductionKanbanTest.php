<?php

use App\Enums\PlateGenerationMode;
use App\Enums\PlateStatus;
use App\Models\LegacyCode;
use App\Models\Plate;
use App\Models\ProductionJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;

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

test('a plate can advance from queued to processing to ready to delivered once the checklist is complete', function () {
    $plate = plateWithJob(PlateStatus::Queued);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'processing'])
        ->assertRedirect();
    expect($plate->fresh()->status)->toBe(PlateStatus::Processing);

    foreach (['front', 'back', 'qr'] as $item) {
        $this->actingAs($this->productionUser)
            ->patch(route('production.plates.checklist', $plate), ['item' => $item, 'checked' => true])
            ->assertRedirect();
    }

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

test('marking a plate ready is blocked until front, back, and QR are all checked off', function () {
    $plate = plateWithJob(PlateStatus::Processing);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'ready'])
        ->assertSessionHasErrors('status');
    expect($plate->fresh()->status)->toBe(PlateStatus::Processing);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.checklist', $plate), ['item' => 'front', 'checked' => true]);
    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.checklist', $plate), ['item' => 'back', 'checked' => true]);

    // Still missing "qr" — still blocked.
    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'ready'])
        ->assertSessionHasErrors('status');

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.checklist', $plate), ['item' => 'qr', 'checked' => true]);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.status', $plate), ['status' => 'ready'])
        ->assertRedirect();
    expect($plate->fresh()->status)->toBe(PlateStatus::Ready);
});

test('a checklist item can be unchecked again', function () {
    $plate = plateWithJob(PlateStatus::Processing);

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.checklist', $plate), ['item' => 'front', 'checked' => true]);
    expect($plate->fresh()->latestProductionJob->front_engraved_at)->not->toBeNull();

    $this->actingAs($this->productionUser)
        ->patch(route('production.plates.checklist', $plate), ['item' => 'front', 'checked' => false]);
    expect($plate->fresh()->latestProductionJob->front_engraved_at)->toBeNull();
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

test('the board stays on a bounded query budget with ~30 plates in production', function () {
    foreach (range(1, 30) as $i) {
        $status = [PlateStatus::Queued, PlateStatus::Processing, PlateStatus::Ready, PlateStatus::Delivered][$i % 4];
        plateWithJob($status);
    }

    DB::enableQueryLog();

    $response = $this->actingAs($this->productionUser)->get(route('production.index'));

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    $response->assertOk();
    // A handful of fixed queries (auth, permissions, the eager-loaded plate
    // query itself) — must NOT scale with the number of plates, or the
    // board will crawl once a real event's production volume lands here.
    expect($queryCount)->toBeLessThan(15);
});

test('production.view alone cannot mutate plate status', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['production.access', 'production.view']);
    $plate = plateWithJob(PlateStatus::Queued);

    $this->actingAs($viewer)
        ->patch(route('production.plates.status', $plate), ['status' => 'processing'])
        ->assertForbidden();
});
