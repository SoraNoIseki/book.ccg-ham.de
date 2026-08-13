<?php

namespace Soranoiseki\BookGroup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Protects the public /api/website/* routes consumed by the WordPress
 * plugin. WordPress sends a static shared-secret token (configured in its
 * own admin settings) in the X-Website-Api-Token header; the expected
 * value lives in this app's .env as WEBSITE_API_TOKEN.
 */
class VerifyWebsiteApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('book.website_api.token');
        $provided = $request->header('X-Website-Api-Token', '');

        if (empty($expected) || !hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
