<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        @header_remove('X-Powered-By');

        $response = $next($request);

        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (!$response->headers->has('Content-Security-Policy')) {
            $csp = "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'; "
                . "connect-src 'self' https: wss: ws:; "
                . "img-src 'self' data: blob: https:; "
                . "font-src 'self' data: https: fonts.gstatic.com; "
                . "style-src 'self' 'unsafe-inline' https: fonts.googleapis.com; "
                . "frame-ancestors 'self';";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
