<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Every /api/v1 success response shares one envelope: {data, message, meta}.
 * Resource-backed responses get this for free via `->additional(...)`;
 * this trait covers the handful of endpoints (auth) with no Eloquent
 * resource behind them.
 */
trait ApiResponses
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function respond(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'meta' => (object) [...$meta, 'request_id' => request()->attributes->get('request_id')],
        ], $status);
    }
}
