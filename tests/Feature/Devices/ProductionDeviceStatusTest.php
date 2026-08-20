<?php

use App\Enums\ProductionDeviceStatus;
use App\Models\ProductionDevice;

test('an active device with a recent heartbeat is online', function () {
    $device = ProductionDevice::factory()->create([
        'status' => ProductionDeviceStatus::Active,
        'last_seen_at' => now(),
    ]);

    expect($device->isOnline())->toBeTrue();
});

test('an active device with a stale heartbeat is offline', function () {
    config(['finisher.device_online_timeout_seconds' => 90]);

    $device = ProductionDevice::factory()->create([
        'status' => ProductionDeviceStatus::Active,
        'last_seen_at' => now()->subSeconds(200),
    ]);

    expect($device->isOnline())->toBeFalse();
});

test('a device that has never sent a heartbeat is offline', function () {
    $device = ProductionDevice::factory()->create([
        'status' => ProductionDeviceStatus::Active,
        'last_seen_at' => null,
    ]);

    expect($device->isOnline())->toBeFalse();
});

test('a pending or revoked device is never online, even with a fresh heartbeat', function () {
    $pending = ProductionDevice::factory()->pending()->create(['last_seen_at' => now()]);
    $revoked = ProductionDevice::factory()->revoked()->create(['last_seen_at' => now()]);

    expect($pending->isOnline())->toBeFalse()
        ->and($revoked->isOnline())->toBeFalse();
});

test('capabilities round-trip as an array', function () {
    $device = ProductionDevice::factory()->create([
        'capabilities' => ['laser_type' => 'fiber', 'power_w' => 30, 'work_area_mm' => ['width' => 200, 'height' => 200]],
    ]);

    expect($device->fresh()->capabilities)->toBe([
        'laser_type' => 'fiber',
        'power_w' => 30,
        'work_area_mm' => ['width' => 200, 'height' => 200],
    ]);
});
