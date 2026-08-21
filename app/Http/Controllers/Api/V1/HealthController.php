<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Deliberately minimal — status only, no DB/queue internals, no version
 * strings beyond the API contract version (docs/api/v1.md §Health). A
 * public, unauthenticated liveness check a load balancer or a future
 * client's "is the backend up" probe can hit safely.
 */
class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'api_version' => config('finisher.api_version'),
        ]);
    }
}
