<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The mirror image of EnsureProductionDeviceToken: a ProductionDevice's
 * Sanctum token must never authenticate an athlete/operator `/api/v1/*`
 * route (profile, medals, claim) — Sanctum's guard alone doesn't stop it,
 * since it happily resolves `$request->user()` to any tokenable model.
 */
class EnsureUserToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('sanctum') instanceof User) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
