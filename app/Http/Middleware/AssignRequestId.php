<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every `/api/*` request gets a correlation id — reuses the caller's
 * `X-Request-ID` if it sent one (so a future Desktop/Mobile client can
 * generate its own and trace a request end-to-end through its own logs),
 * otherwise generates a UUID. Echoed back on the response header and
 * available to App\Support\Api\ApiExceptionRenderer /
 * App\Support\Devices\DeviceExceptionRenderer for the error envelope, and
 * to any log line via `request()->attributes->get('request_id')` — never
 * logged alongside a token or other secret (docs/api/v1.md §Observabilidad).
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
