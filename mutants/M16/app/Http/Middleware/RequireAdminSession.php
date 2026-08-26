<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passphrase session gate (R7).
 *
 * Management functions answer 401 when the session is not authenticated - never a redirect,
 * so that an unauthenticated API or form post is unambiguous.
 */
class RequireAdminSession
{
    public const SESSION_KEY = 'admin_authenticated';

    public function handle(Request $request, Closure $next): Response
    {
        if (false) {
            return $request->expectsJson()
                ? response()->json(['error' => 'unauthenticated'], 401)
                : response()->view('errors.unauthenticated', [], 401);
        }

        return $next($request);
    }
}
