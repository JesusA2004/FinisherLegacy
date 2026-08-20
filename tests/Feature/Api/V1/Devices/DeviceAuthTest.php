<?php

use App\Actions\Devices\RevokeProductionDevice;
use App\Enums\DeviceAbility;
use App\Models\ProductionDevice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function deviceToken(ProductionDevice $device, ?array $abilities = null): string
{
    return $device->createToken('test', $abilities ?? DeviceAbility::all())->plainTextToken;
}

test('a device token can heartbeat and read production', function () {
    $device = ProductionDevice::factory()->create();
    $token = deviceToken($device);

    $this->withToken($token)->getJson('/api/v1/device')->assertOk();
    $this->withToken($token)->getJson('/api/v1/production/jobs/next')->assertOk();
});

test('a device token cannot call non-device /api/v1 routes', function () {
    $device = ProductionDevice::factory()->create();
    $token = deviceToken($device);

    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    $this->withToken($token)->getJson('/api/v1/profile')->assertUnauthorized();
});

test('a device token cannot reach the admin web panel', function () {
    $device = ProductionDevice::factory()->create();
    $token = deviceToken($device);

    // The admin panel uses the session-based `web` guard — a bearer token
    // simply isn't a session, so this is unauthenticated regardless.
    $this->withToken($token)->get('/admin/users')->assertRedirect();
});

test('a user token cannot call device routes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/device')->assertUnauthorized();
    $this->withToken($token)->getJson('/api/v1/production/jobs/next')->assertUnauthorized();
});

test('a request without a token is rejected', function () {
    $this->getJson('/api/v1/device')->assertUnauthorized();
});

test('a revoked device token stops working immediately', function () {
    $device = ProductionDevice::factory()->create();
    $token = deviceToken($device);

    $this->withToken($token)->getJson('/api/v1/device')->assertOk();

    app(RevokeProductionDevice::class)->handle($device);

    // Sanctum's guard caches the resolved user on the guard instance for
    // the lifetime of the container — real requests each get a fresh
    // container, but two sequential test calls in one method share one, so
    // the guard must be forgotten to force it to re-check the token.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/device')->assertUnauthorized();
});

test('a device without the required ability is forbidden', function () {
    $device = ProductionDevice::factory()->create();
    $token = deviceToken($device, [DeviceAbility::Heartbeat->value]);

    $this->withToken($token)->getJson('/api/v1/production/jobs/next')->assertForbidden();
});
