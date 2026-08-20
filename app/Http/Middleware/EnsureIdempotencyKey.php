<?php

namespace App\Http\Middleware;

use App\Models\DeviceIdempotencyKey;
use App\Models\ProductionDevice;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional `Idempotency-Key` header support for device write endpoints
 * (currently: job claim). A `Cache::lock()` (works with the default
 * `database` cache store, no Redis required) serializes truly concurrent
 * requests with the same key — without it, two parallel retries could both
 * miss the "already recorded" check and both execute the action, which
 * would defeat the point. The lock is per device+route+key, so it never
 * blocks unrelated requests.
 */
class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        // Explicit 'sanctum' guard — see EnsureProductionDeviceToken for why
        // not the no-arg $request->user().
        $device = $request->user('sanctum');

        if ($key === null || ! $device instanceof ProductionDevice) {
            return $next($request);
        }

        $route = (string) $request->route()?->getName();
        $lockName = "device-idempotency:{$device->id}:{$route}:{$key}";

        return Cache::lock($lockName, 10)->block(5, function () use ($request, $next, $device, $route, $key) {
            $existing = DeviceIdempotencyKey::query()
                ->where('production_device_id', $device->id)
                ->where('route', $route)
                ->where('key', $key)
                ->first();

            if ($existing !== null) {
                return response()->json($existing->response_body, $existing->response_status)
                    ->header('Idempotency-Replayed', 'true');
            }

            $response = $next($request);

            if ($response->getStatusCode() < 500) {
                $this->record($device, $route, $key, $response);
            }

            return $response;
        });
    }

    private function record(ProductionDevice $device, string $route, string $key, Response $response): void
    {
        try {
            DeviceIdempotencyKey::query()->create([
                'production_device_id' => $device->id,
                'route' => $route,
                'key' => $key,
                'response_status' => $response->getStatusCode(),
                'response_body' => json_decode((string) $response->getContent(), true),
            ]);
        } catch (QueryException) {
            // Unique constraint race with another request for the same
            // key — the response already computed above is still correct
            // to return; nothing further to do.
        }
    }
}
