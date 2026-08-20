<?php

namespace App\Actions\Integrations;

use App\Enums\ProviderConnectionStatus;
use App\Models\ProviderConnection;
use App\Services\Integrations\EventProviderRegistry;
use App\Support\Integrations\ProviderConnectionTestResult;
use Throwable;

/**
 * Never persists a secret — only the pass/fail outcome and a timestamp
 * (docs/adr/0005 §Security).
 */
class TestProviderConnection
{
    public function __construct(private readonly EventProviderRegistry $registry) {}

    public function handle(ProviderConnection $connection): ProviderConnectionTestResult
    {
        try {
            $result = $this->registry->get($connection->provider_key)->testConnection($connection);
        } catch (Throwable) {
            $result = new ProviderConnectionTestResult(false, 0, [], 'No se pudo contactar al proveedor.');
        }

        $connection->update([
            'status' => $result->success ? ProviderConnectionStatus::Connected : ProviderConnectionStatus::Failed,
            'last_tested_at' => now(),
        ]);

        return $result;
    }
}
