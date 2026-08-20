<?php

use App\Enums\ProviderConnectionStatus;
use App\Models\ProviderConnection;
use App\Services\Integrations\Providers\MockEventProviderAdapter;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->adapter = new MockEventProviderAdapter;
    $this->connection = ProviderConnection::create([
        'uuid' => (string) Str::uuid(),
        'provider_key' => 'mock',
        'name' => 'Mock',
        'status' => ProviderConnectionStatus::Untested,
        'settings' => ['chunk_size' => 40],
    ]);
});

test('test connection succeeds by default', function () {
    $result = $this->adapter->testConnection($this->connection);

    expect($result->success)->toBeTrue();
});

test('test connection can be forced to fail via settings', function () {
    $this->connection->update(['settings' => ['simulate_test_failure' => true]]);

    $result = $this->adapter->testConnection($this->connection);

    expect($result->success)->toBeFalse();
});

test('lists exactly one mock event with two races', function () {
    $events = $this->adapter->listEvents($this->connection);

    expect($events)->toHaveCount(1)
        ->and($events[0]->races)->toHaveCount(2);
});

test('fetches exactly 100 participants across pages', function () {
    $all = [];
    $cursor = null;

    do {
        $page = $this->adapter->fetchParticipants($this->connection, MockEventProviderAdapter::EVENT_EXTERNAL_ID, $cursor, 40);
        array_push($all, ...$page->items);
        $cursor = $page->nextCursor;
    } while ($page->hasMore);

    expect($all)->toHaveCount(100)
        ->and(collect($all)->pluck('externalParticipantId')->unique())->toHaveCount(100);
});

test('fetchResults returns only as many finishers as mock_finishers_count allows', function () {
    $this->connection->update(['settings' => ['mock_finishers_count' => 20]]);

    $page = $this->adapter->fetchResults($this->connection, MockEventProviderAdapter::EVENT_EXTERNAL_ID, null, 100);

    expect($page->items)->toHaveCount(20)
        ->and($page->hasMore)->toBeFalse();
});
