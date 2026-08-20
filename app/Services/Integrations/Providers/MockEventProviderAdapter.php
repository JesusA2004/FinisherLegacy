<?php

namespace App\Services\Integrations\Providers;

use App\Contracts\Integrations\EventProviderAdapter;
use App\Models\ProviderConnection;
use App\Support\Integrations\ExternalEventData;
use App\Support\Integrations\ExternalPage;
use App\Support\Integrations\ExternalParticipantData;
use App\Support\Integrations\ExternalResultData;
use App\Support\Integrations\ProviderConnectionTestResult;

/**
 * Simulates a timing-company API with no credentials and no hardware, so
 * the full ingestion pipeline can be exercised end to end before any real
 * provider is connected (docs/adr/0005 §Mock provider). Deterministic and
 * pure aside from reading `$connection->settings` — nothing here is
 * randomized, so a test asserting "100 participants" always gets exactly
 * 100.
 *
 * The "live event" illusion (0 → 20 → 55 → 100 finishers) is driven
 * entirely by `settings.mock_finishers_count` on the connection —
 * `php artisan finisher:simulate-live-event` advances it between syncs.
 * This adapter has no internal mutable state of its own, which is what
 * keeps it safe to resolve as a singleton.
 */
class MockEventProviderAdapter implements EventProviderAdapter
{
    public const PARTICIPANT_COUNT = 100;

    public const EVENT_EXTERNAL_ID = 'EVT-MOCK-1';

    public const RACE_21K_EXTERNAL_ID = 'RACE-21K';

    public const RACE_42K_EXTERNAL_ID = 'RACE-42K';

    public function key(): string
    {
        return 'mock';
    }

    public function testConnection(ProviderConnection $connection): ProviderConnectionTestResult
    {
        $start = microtime(true);
        $shouldFail = (bool) ($connection->settingsArray()['simulate_test_failure'] ?? false);
        $latencyMs = (int) round((microtime(true) - $start) * 1000) + 5;

        if ($shouldFail) {
            return new ProviderConnectionTestResult(false, $latencyMs, [], 'Fallo simulado (simulate_test_failure).');
        }

        return new ProviderConnectionTestResult(
            success: true,
            latencyMs: $latencyMs,
            providerInfo: ['provider' => 'Mock Event Provider', 'version' => '1.0'],
            message: 'Conexión simulada correcta.',
        );
    }

    public function listEvents(ProviderConnection $connection): array
    {
        return [$this->buildEvent()];
    }

    public function fetchEvent(ProviderConnection $connection, string $externalEventId): ExternalEventData
    {
        return $this->buildEvent();
    }

    public function fetchParticipants(ProviderConnection $connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage
    {
        $offset = $cursor !== null ? (int) $cursor : 0;
        $all = $this->generateParticipants();
        $slice = array_slice($all, $offset, $chunkSize);
        $nextOffset = $offset + count($slice);
        $hasMore = $nextOffset < count($all);

        return new ExternalPage($slice, $hasMore ? (string) $nextOffset : null, $hasMore);
    }

    public function fetchResults(ProviderConnection $connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage
    {
        $finisherCount = min(self::PARTICIPANT_COUNT, max(0, (int) ($connection->settingsArray()['mock_finishers_count'] ?? 0)));
        $offset = $cursor !== null ? (int) $cursor : 0;

        $all = $this->generateResults($finisherCount);
        $slice = array_slice($all, $offset, $chunkSize);
        $nextOffset = $offset + count($slice);
        $hasMore = $nextOffset < count($all);

        return new ExternalPage($slice, $hasMore ? (string) $nextOffset : null, $hasMore);
    }

    public function supportsIncrementalSync(): bool
    {
        return true;
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }

    private function buildEvent(): ExternalEventData
    {
        return ExternalEventData::fromArray([
            'external_id' => self::EVENT_EXTERNAL_ID,
            'name' => 'Maratón Mock 2026',
            'year' => 2026,
            'date' => '2026-11-15',
            'timezone' => 'America/Mexico_City',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'country' => 'MX',
            'status' => 'scheduled',
            'races' => [
                ['external_id' => self::RACE_21K_EXTERNAL_ID, 'name' => '21K', 'distance_value' => 21.097, 'distance_unit' => 'km', 'race_type' => 'running'],
                ['external_id' => self::RACE_42K_EXTERNAL_ID, 'name' => '42K', 'distance_value' => 42.195, 'distance_unit' => 'km', 'race_type' => 'running'],
            ],
        ]);
    }

    /**
     * @return list<ExternalParticipantData>
     */
    private function generateParticipants(): array
    {
        $participants = [];

        for ($i = 1; $i <= self::PARTICIPANT_COUNT; $i++) {
            $race = $i % 2 === 0 ? self::RACE_42K_EXTERNAL_ID : self::RACE_21K_EXTERNAL_ID;

            $participants[] = ExternalParticipantData::fromArray([
                'external_participant_id' => "P-{$i}",
                'external_athlete_id' => "ATH-{$i}",
                'bib_number' => (string) (1000 + $i),
                'first_name' => 'Corredor',
                'last_name' => (string) $i,
                'email' => "corredor{$i}@mock.test",
                'gender' => $i % 2 === 0 ? 'F' : 'M',
                'category' => $i % 2 === 0 ? 'F30-34' : 'M30-34',
                'race_external_id' => $race,
                'status' => 'registered',
            ]);
        }

        return $participants;
    }

    /**
     * @return list<ExternalResultData>
     */
    private function generateResults(int $finisherCount): array
    {
        $results = [];

        for ($i = 1; $i <= $finisherCount; $i++) {
            $totalMinutes = 180 + ($i * 2);
            $officialTime = sprintf('%02d:%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60, ($i * 7) % 60);

            $results[] = ExternalResultData::fromArray([
                'external_participant_id' => "P-{$i}",
                'external_result_id' => "R-{$i}",
                'official_time' => $officialTime,
                'status' => 'finished',
                'overall_position' => $i,
                'splits' => [
                    ['type' => 'split', 'label' => '21K', 'sequence' => 1, 'elapsed_time' => sprintf('%02d:%02d:%02d', intdiv($totalMinutes, 120), ($totalMinutes / 2) % 60, 0)],
                ],
            ]);
        }

        return $results;
    }
}
