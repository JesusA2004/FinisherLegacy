<?php

namespace App\Contracts\Integrations;

use App\Models\ProviderConnection;
use Illuminate\Http\Request;

/**
 * Optional capability — only adapters where `supportsWebhooks()` is true
 * implement this. No generic webhook endpoint ever trusts a payload
 * without a matching adapter verifying signature/timestamp first (docs/adr/0005
 * §Webhook contract, §80). No route is registered for this yet — Slice 4
 * ships the contract, not a live endpoint (§79: never public without a
 * real, secure implementation behind it).
 */
interface VerifiesWebhooks
{
    public function verifyWebhook(ProviderConnection $connection, Request $request): bool;
}
