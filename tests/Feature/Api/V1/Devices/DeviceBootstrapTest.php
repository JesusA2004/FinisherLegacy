<?php

use App\Enums\DeviceAbility;
use App\Models\EventEdition;
use App\Models\ProductionDevice;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function bootstrapDeviceToken(ProductionDevice $device): string
{
    return $device->createToken('test', DeviceAbility::all())->plainTextToken;
}

test('device bootstrap returns device, active event, server time, and api version, with no current job', function () {
    $edition = EventEdition::factory()->create();
    $device = ProductionDevice::factory()->create(['event_edition_id' => $edition->id]);
    $token = bootstrapDeviceToken($device);

    $response = $this->withToken($token)->getJson('/api/v1/device/bootstrap');

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['device', 'current_job', 'server_time', 'api_version', 'minimum_supported_client_version']]);
    expect($response->json('data.device.event_edition.id'))->toBe($edition->id)
        ->and($response->json('data.current_job'))->toBeNull();
});

test('the existing GET /device endpoint keeps its original flat shape', function () {
    $device = ProductionDevice::factory()->create();
    $token = bootstrapDeviceToken($device);

    $response = $this->withToken($token)->getJson('/api/v1/device');

    $response->assertOk();
    expect($response->json('data.uuid'))->toBe($device->uuid);
});
