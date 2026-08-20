<?php

namespace App\Actions\Integrations;

use App\Models\EventEdition;
use App\Models\ExternalEventMapping;
use App\Models\ProviderConnection;

/**
 * Links an already-existing EventEdition to a provider's external event
 * id. `updateOrCreate` on the unique pair makes linking the same external
 * event twice a no-op instead of a unique-constraint exception
 * (docs/adr/0005 §23 — never duplicates the EventEdition).
 */
class LinkExternalEvent
{
    public function handle(ProviderConnection $connection, string $externalEventId, EventEdition $edition): ExternalEventMapping
    {
        return ExternalEventMapping::query()->updateOrCreate(
            ['provider_connection_id' => $connection->id, 'external_event_id' => $externalEventId],
            ['event_edition_id' => $edition->id],
        );
    }
}
