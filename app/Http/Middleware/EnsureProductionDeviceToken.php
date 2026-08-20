<?php

namespace App\Http\Middleware;

use App\Enums\ProductionDeviceStatus;
use App\Exceptions\Devices\DeviceRevokedException;
use App\Models\ProductionDevice;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after `auth:sanctum` on every Device API route. Sanctum's guard
 * resolves `$request->user()` to WHATEVER model a token belongs to — it
 * does not itself stop a User's Sanctum token from authenticating a device
 * route. This is what makes that boundary real, symmetric with
 * EnsureUserToken on the /api/v1 non-device routes.
 */
class EnsureProductionDeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Explicit 'sanctum' guard (not the no-arg default) so this reads
        // as "whatever Sanctum resolved," never assumed to be a User —
        // `$request->user()` with no argument is statically typed by
        // Larastan as the app's default-guard model (User) project-wide,
        // which would make the instanceof check below never even compile
        // cleanly against reality here.
        $device = $request->user('sanctum');

        if (! $device instanceof ProductionDevice) {
            throw new AuthenticationException;
        }

        if ($device->status === ProductionDeviceStatus::Revoked) {
            throw new DeviceRevokedException;
        }

        return $next($request);
    }
}
