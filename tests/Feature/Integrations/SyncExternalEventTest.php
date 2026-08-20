<?php

use App\Actions\Integrations\CreateEventFromExternalData;
use App\Actions\Integrations\SyncExternalEvent;
use App\Enums\ExternalSyncStatus;
use App\Enums\ExternalSyncType;
use App\Enums\ProviderConnectionStatus;
use App\Models\Athlete;
use App\Models\EventParticipant;
use App\Models\EventRace;
use App\Models\EventResult;
use App\Models\EventResultSplit;
use App\Models\ExternalEventMapping;
use App\Models\ProviderConnection;
use App\Models\Sport;
use App\Services\Integrations\EventProviderRegistry;
use Illuminate\Support\Str;

function makeMockMapping(): ExternalEventMapping
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

test('the first roster sync creates exactly 100 participants and one race mapping per race', function () {
    $mapping = makeMockMapping();

    $run = app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    expect($run->status)->toBe(ExternalSyncStatus::Completed)
        ->and($run->participants_created)->toBe(100)
        ->and($run->participants_updated)->toBe(0)
        ->and(EventParticipant::where('event_edition_id', $mapping->event_edition_id)->count())->toBe(100)
        ->and(EventRace::where('event_edition_id', $mapping->event_edition_id)->count())->toBe(2);
});

test('re-syncing the same roster updates existing participants instead of duplicating them', function () {
    $mapping = makeMockMapping();
    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    $run = app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    expect($run->participants_created)->toBe(0)
        ->and($run->participants_updated)->toBe(100)
        ->and(EventParticipant::where('event_edition_id', $mapping->event_edition_id)->count())->toBe(100);
});

test('syncing the same external event twice never creates a second EventEdition', function () {
    $mapping = makeMockMapping();
    $editionId = $mapping->event_edition_id;

    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);
    $again = app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Roster);

    expect($again->event_edition_id)->toBe($editionId)
        ->and(ExternalEventMapping::count())->toBe(1);
});

test('results sync creates results incrementally as the mock clock advances, without duplicating', function () {
    $mapping = makeMockMapping();
    $connection = $mapping->providerConnection;
    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);

    $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => 20])]);
    $run1 = app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);
    expect($run1->results_created)->toBe(20)
        ->and(EventResult::count())->toBe(20);

    $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => 55])]);
    $run2 = app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);
    expect($run2->results_created)->toBe(35)
        ->and(EventResult::count())->toBe(55);

    $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => 100])]);
    $run3 = app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);
    expect($run3->results_created)->toBe(45)
        ->and(EventResult::count())->toBe(100)
        ->and(EventResultSplit::count())->toBe(100);
});

test('a provider result update changes the existing EventResult instead of creating a second one', function () {
    $mapping = makeMockMapping();
    $connection = $mapping->providerConnection;
    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);
    $connection->update(['settings' => array_merge($connection->settingsArray(), ['mock_finishers_count' => 5])]);
    app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);

    $participant = EventParticipant::where('event_edition_id', $mapping->event_edition_id)->where('bib_number', '1001')->firstOrFail();
    $originalTime = $participant->result->official_time;

    // Re-run with the same finisher count — the mock generator is
    // deterministic, so the time is identical, proving idempotency rather
    // than a real "correction" here.
    app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Results);

    expect(EventResult::where('event_participant_id', $participant->id)->count())->toBe(1)
        ->and($participant->result()->first()->official_time)->toBe($originalTime);
});

test('no duplicate athletes are created across two identical roster syncs', function () {
    $mapping = makeMockMapping();
    app(SyncExternalEvent::class)->handle($mapping, ExternalSyncType::Roster);
    $athletesAfterFirst = Athlete::count();

    app(SyncExternalEvent::class)->handle($mapping->fresh(), ExternalSyncType::Roster);

    expect(Athlete::count())->toBe($athletesAfterFirst)
        ->and($athletesAfterFirst)->toBe(100);
});
