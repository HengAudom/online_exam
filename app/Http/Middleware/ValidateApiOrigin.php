<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiOrigin
{
    /**
     * Prevent Cross-Site Request Forgery (CSRF) on state-changing authenticated API requests
     * by verifying that the request originates from the same host.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->method();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $origin = $request->headers->get('Origin');
            $referer = $request->headers->get('Referer');

            // Enforce on requests carrying authenticated sessions
            if ($request->user() || $request->hasCookie(config('session.cookie', 'online_exam_session'))) {
                $appHost = strtolower($request->getHost());

                if ($origin) {
                    $originHost = strtolower(parse_url($origin, PHP_URL_HOST) ?? '');
                    if ($originHost !== '' && $originHost !== $appHost && !in_array($originHost, ['localhost', '127.0.0.1'])) {
                        return response()->json([
                            'message' => 'Cross-origin state-changing request forbidden.'
                        ], 403);
                    }
                } elseif ($referer) {
                    $refererHost = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
                    if ($refererHost !== '' && $refererHost !== $appHost && !in_array($refererHost, ['localhost', '127.0.0.1'])) {
                        return response()->json([
                            'message' => 'Cross-origin state-changing request forbidden.'
                        ], 403);
                    }
                }
            }
        }

        return $next($request);
    }
}
