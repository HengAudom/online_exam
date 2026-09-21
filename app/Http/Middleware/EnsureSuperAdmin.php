<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $role = $user->role ?? $user->Role ?? null;

        if (!in_array($role, ['Super Admin', 'SuperAdmin'], true)) {
            return response()->json([
                'message' => 'Forbidden. Super Admin privileges required.'
            ], 403);
        }

        return $next($request);
    }
}
