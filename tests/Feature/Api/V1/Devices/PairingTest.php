<?php

use App\Actions\Devices\ApproveDevicePairing;
use App\Enums\DevicePairingStatus;
use App\Models\DevicePairingRequest;
use App\Models\User;
use App\Support\Devices\ApproveDevicePairingData;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function approvePairing(DevicePairingRequest $pairingRequest): DevicePairingRequest
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    app(ApproveDevicePairing::class)->handle(
        $pairingRequest,
        ApproveDevicePairingData::fromRequest(Request::create('/', 'POST', [])),
        $admin,
    );

    return $pairingRequest->fresh();
}

test('a device can request a pairing code', function () {
    $response = $this->postJson('/api/v1/devices/pair', ['name' => 'Estación 1']);

    $response->assertCreated();
    $response->assertJsonStructure(['data' => ['code', 'poll_token', 'expires_at'], 'message']);

    expect(DevicePairingRequest::where('code', $response->json('data.code'))->exists())->toBeTrue();
});

test('confirming a pending (not yet approved) pairing returns a pending status, not an error', function () {
    $pair = $this->postJson('/api/v1/devices/pair', ['name' => 'Estación 1'])->json('data');

    $response = $this->postJson('/api/v1/devices/pair/confirm', ['poll_token' => $pair['poll_token']]);

    $response->assertOk();
    $response->assertJsonPath('data.status', 'pending');
});

test('confirming an approved pairing delivers a token exactly once', function () {
    $pair = $this->postJson('/api/v1/devices/pair', ['name' => 'Estación 1'])->json('data');
    $pairingRequest = DevicePairingRequest::where('code', $pair['code'])->firstOrFail();
    approvePairing($pairingRequest);

    $first = $this->postJson('/api/v1/devices/pair/confirm', ['poll_token' => $pair['poll_token']]);
    $first->assertOk();
    $first->assertJsonPath('data.status', 'completed');
    expect($first->json('data.token'))->toBeString();

    $second = $this->postJson('/api/v1/devices/pair/confirm', ['poll_token' => $pair['poll_token']]);
    $second->assertStatus(410);
    $second->assertJsonPath('error.code', 'PAIRING_ALREADY_COMPLETED');
});

test('confirming an expired pairing is rejected', function () {
    $pair = $this->postJson('/api/v1/devices/pair', ['name' => 'Estación 1'])->json('data');
    $pairingRequest = DevicePairingRequest::where('code', $pair['code'])->firstOrFail();
    $pairingRequest->update(['expires_at' => now()->subMinute()]);

    $response = $this->postJson('/api/v1/devices/pair/confirm', ['poll_token' => $pair['poll_token']]);

    $response->assertStatus(410);
    $response->assertJsonPath('error.code', 'PAIRING_EXPIRED');
    expect($pairingRequest->fresh()->status)->toBe(DevicePairingStatus::Expired);
});

test('confirming with an unknown poll token is rejected', function () {
    $response = $this->postJson('/api/v1/devices/pair/confirm', ['poll_token' => 'not-a-real-token']);

    $response->assertStatus(404);
    $response->assertJsonPath('error.code', 'PAIRING_REQUEST_NOT_FOUND');
});
