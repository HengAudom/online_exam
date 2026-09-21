<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AdminController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $module
     * @param  string  $action
     */
    public function handle(Request $request, Closure $next, ?string $module = null, string $action = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $role = $user->role ?? $user->Role ?? null;

        if (!in_array($role, ['Admin', 'Super Admin', 'SuperAdmin'], true)) {
            return response()->json([
                'message' => 'Forbidden. Admin privileges required.'
            ], 403);
        }

        if ($module !== null && !AdminController::checkAdminPermission($user, $module, $action)) {
            return response()->json([
                'message' => "Unauthorized. You do not have permission to {$action} in {$module}."
            ], 403);
        }

        return $next($request);
    }
}
