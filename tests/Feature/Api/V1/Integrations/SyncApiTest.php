<?php

use App\Enums\ProviderConnectionStatus;
use App\Jobs\SyncExternalEventJob;
use App\Models\EventEdition;
use App\Models\ExternalEventMapping;
use App\Models\ProviderConnection;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function apiIntegrationsAuthHeader(): array
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
}

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('triggering a sync via the API dispatches the same job Web uses and responds 202', function () {
    Queue::fake();
    $headers = apiIntegrationsAuthHeader();
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(), 'provider_key' => 'mock', 'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
    ]);
    $edition = EventEdition::factory()->create();
    $mapping = ExternalEventMapping::create([
        'provider_connection_id' => $connection->id, 'external_event_id' => 'EVT-1', 'event_edition_id' => $edition->id,
    ]);

    $response = $this->withHeaders($headers)->postJson("/api/v1/integrations/mappings/{$mapping->id}/sync", ['sync_type' => 'roster']);

    $response->assertStatus(202);
    Queue::assertPushed(SyncExternalEventJob::class, fn ($job) => $job->eventMappingId === $mapping->id);
});

test('latest sync run 404s cleanly with the unified error envelope when nothing has run yet', function () {
    $headers = apiIntegrationsAuthHeader();
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(), 'provider_key' => 'mock', 'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
    ]);
    $edition = EventEdition::factory()->create();
    $mapping = ExternalEventMapping::create([
        'provider_connection_id' => $connection->id, 'external_event_id' => 'EVT-1', 'event_edition_id' => $edition->id,
    ]);

    $response = $this->withHeaders($headers)->getJson("/api/v1/integrations/mappings/{$mapping->id}/sync-runs/latest");

    $response->assertStatus(404);
    expect($response->json('error.code'))->toBe('NOT_FOUND');
});
