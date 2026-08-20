<?php

namespace App\Contracts\Integrations;

use App\Models\ProviderConnection;
use App\Support\Integrations\ExternalEventData;
use App\Support\Integrations\ExternalPage;
use App\Support\Integrations\ExternalParticipantData;
use App\Support\Integrations\ExternalResultData;
use App\Support\Integrations\ProviderConnectionTestResult;

/**
 * What every event/timing source looks like once normalized — CSV, a mock,
 * or a real REST provider all implement this and produce the same
 * canonical DTOs (App\Support\Integrations\*Data). Nothing downstream ever
 * sees a provider's native shape. See
 * docs/adr/0005-unified-event-ingestion.md §Provider contract.
 *
 * Capability methods (`supportsIncrementalSync`, `supportsWebhooks`) exist
 * so an adapter never has to fake support for something it can't do —
 * callers check the capability before relying on the behavior.
 */
interface EventProviderAdapter
{
    /**
     * The registry key this adapter is resolved by (e.g. "mock").
     */
    public function key(): string;

    public function testConnection(ProviderConnection $connection): ProviderConnectionTestResult;

    /**
     * @return list<ExternalEventData>
     */
    public function listEvents(ProviderConnection $connection): array;

    public function fetchEvent(ProviderConnection $connection, string $externalEventId): ExternalEventData;

    /**
     * @return ExternalPage<ExternalParticipantData>
     */
    public function fetchParticipants(ProviderConnection $connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage;

    /**
     * @return ExternalPage<ExternalResultData>
     */
    public function fetchResults(ProviderConnection $connection, string $externalEventId, ?string $cursor, int $chunkSize): ExternalPage;

    public function supportsIncrementalSync(): bool;

    public function supportsWebhooks(): bool;
}
