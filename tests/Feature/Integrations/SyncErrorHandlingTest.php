<?php

use App\Actions\Integrations\CreateEventFromExternalData;
use App\Actions\Integrations\SyncExternalEvent;
use App\Contracts\Integrations\EventProviderAdapter;
use App\Enums\ExternalSyncStatus;
use App\Enums\ExternalSyncType;
use App\Enums\ProviderConnectionStatus;
use App\Exceptions\Integrations\ProviderUnavailableException;
use App\Models\EventParticipant;
use App\Models\ExternalEventMapping;
use App\Models\ExternalParticipantMapping;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Services\Integrations\EventProviderRegistry;
use App\Support\Integrations\ExternalEventData;
use App\Support\Integrations\ExternalPage;
use App\Support\Integrations\ProviderConnectionTestResult;
use Illuminate\Support\Str;

function mockMappingForErrorTests(): ExternalEventMapping
{
    $connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(),
        'provider_key' => 'mock',
        'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
        'settings' => ['chunk_size' => 40],
    ]);

    $adapter = app(EventProviderRegistry::class)->get('mock');
    $eventData = $adapter->listEvents($connection)[0];
    $sport = Sport::factory()->create();

    return app(CreateEventFromExternalData::class)->handle($eventData, $connection, $sport);
}

test('one bad row is isolated as a sync error without blocking the rest', function () {
    $mapping = mockMappingForErrorTests();
    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    $victim = EventParticipant::where('event_edition_id', $mapping->event_edition_id)->where('bib_number', '1050')->firstOrFail();
    ExternalParticipantMapping::where('event_participant_id', $victim->id)->delete();
    $victim->delete();

    $connection = $mapping->providerConnection;
    $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => 60])]);

    $run = app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);

    expect($run->status)->toBe(ExternalSyncStatus::Partial)
        ->and($run->errors_count)->toBe(1)
        ->and($run->results_created)->toBe(59)
        ->and($run->errors->first()->code)->toBe('PARTICIPANT_NOT_FOUND');
});

test('a provider outage fails the whole run without persisting partial data', function () {
    $mapping = mockMappingForErrorTests();

    $throwingAdapter = new class implements EventProviderAdapter
    {
        public function key(): string
        {
            return 'mock';
        }

        public function testConnection($connection): ProviderConnectionTestResult
        {
            throw new ProviderUnavailableException('down');
        }

        public function listEvents($connection): array
        {
            return [];
        }

        public function fetchEvent($connection, string $externalEventId): ExternalEventData
        {
            throw new ProviderUnavailableException('El proveedor no responde.');
        }

        public function fetchParticipants($connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage
        {
            return new ExternalPage([], null, false);
        }

        public function fetchResults($connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage
        {
            return new ExternalPage([], null, false);
        }

        public function supportsIncrementalSync(): bool
        {
            return false;
        }

        public function supportsWebhooks(): bool
        {
            return false;
        }
    };

    $registry = new class($throwingAdapter) extends EventProviderRegistry
    {
        public function __construct(private readonly EventProviderAdapter $adapter) {}

        public function get(string $providerKey): EventProviderAdapter
        {
            return $this->adapter;
        }
    };

    app()->instance(EventProviderRegistry::class, $registry);

    $run = app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    expect($run->status)->toBe(ExternalSyncStatus::Failed)
        ->and($run->errors->first()->code)->toBe('PROVIDER_UNAVAILABLE')
        ->and(EventParticipant::count())->toBe(0);
});
