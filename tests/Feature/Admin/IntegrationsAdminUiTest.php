<?php

use App\Enums\ProviderConnectionStatus;
use App\Jobs\SyncExternalEventJob;
use App\Models\ExternalEventMapping;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('the integrations index is blocked without integrations.view', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.integrations.index'))->assertForbidden();
});

test('an admin can create a mock connection and see it listed', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.integrations.store'), [
        'provider_key' => 'mock',
        'name' => 'Mock Event Provider',
    ]);

    $response->assertRedirect();
    expect(ProviderConnection::where('name', 'Mock Event Provider')->exists())->toBeTrue();

    $index = $this->actingAs($admin)->get(route('admin.integrations.index'));
    $index->assertInertia(fn ($page) => $page
        ->component('admin/integrations/Index')
        ->has('connections.0', fn ($c) => $c
            ->where('name', 'Mock Event Provider')
            ->where('provider_key', 'mock')
            ->etc()
        )
    );
});

test('credentials are never exposed in the connection show payload', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(), 'provider_key' => 'mock', 'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested, 'credentials' => 'super-secret-key',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.integrations.show', $connection));

    $response->assertOk();
    $response->assertDontSee('super-secret-key');
});

test('testing a mock connection marks it connected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(), 'provider_key' => 'mock', 'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
    ]);

    $this->actingAs($admin)->post(route('admin.integrations.test', $connection))->assertRedirect();

    expect($connection->fresh()->status)->toBe(ProviderConnectionStatus::Connected);
});

test('linking a mock event creates an EventEdition and dispatching a sync queues the job', function () {
    Queue::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(), 'provider_key' => 'mock', 'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
    ]);
    $sport = Sport::factory()->create();

    $this->actingAs($admin)->post(route('admin.integrations.events.link', $connection), [
        'external_event_id' => 'EVT-MOCK-1',
        'mode' => 'create',
        'sport_id' => $sport->id,
    ])->assertRedirect();

    $mapping = ExternalEventMapping::where('provider_connection_id', $connection->id)->firstOrFail();

    $this->actingAs($admin)->post(route('admin.integrations.mappings.sync', $mapping), ['sync_type' => 'roster'])
        ->assertRedirect();

    Queue::assertPushed(SyncExternalEventJob::class, fn ($job) => $job->eventMappingId === $mapping->id);
});
