<?php

use App\Enums\DevicePairingStatus;
use App\Models\DevicePairingRequest;
use App\Models\ProductionDevice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('estaciones is blocked without the productiondevices.view permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.production-devices.index'))->assertForbidden();
});

test('an admin sees pending pairings and paired devices', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    DevicePairingRequest::factory()->create(['requested_name' => 'Estación de bodega']);
    ProductionDevice::factory()->create(['name' => 'Estación 1']);

    $response = $this->actingAs($admin)->get(route('admin.production-devices.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/production-devices/Index')
        ->has('pendingPairings', 1)
        ->has('devices', 1)
    );
});

test('an admin can approve a pending pairing, which links a ProductionDevice', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $pairing = DevicePairingRequest::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.production-devices.pairings.approve', $pairing), []);

    $response->assertRedirect();
    $pairing->refresh();
    expect($pairing->status)->toBe(DevicePairingStatus::Approved)
        ->and($pairing->production_device_id)->not->toBeNull();
});

test('approving requires productiondevices.manage, not just .view', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['productiondevices.view']);
    $pairing = DevicePairingRequest::factory()->create();

    $this->actingAs($viewer)
        ->post(route('admin.production-devices.pairings.approve', $pairing), [])
        ->assertForbidden();
});

test('an admin can revoke a device, which deletes its tokens', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $device = ProductionDevice::factory()->create();
    $device->createToken('test');

    $response = $this->actingAs($admin)->post(route('admin.production-devices.revoke', $device), []);

    $response->assertRedirect();
    expect($device->fresh()->status->value)->toBe('revoked')
        ->and($device->fresh()->tokens()->count())->toBe(0);
});
