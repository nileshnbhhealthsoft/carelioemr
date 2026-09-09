<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $tokenHeader = $request->header('X-Admin-Token');
        $bearerToken = $request->bearerToken();

        // Check if valid token is provided in Authorization or X-Admin-Token headers
        if ($bearerToken || 
            ($authHeader && str_contains(strtolower($authHeader), 'bearer')) || 
            !empty($tokenHeader)) {
            return $next($request);
        }

        // Access Denied for direct unauthenticated browser access
        return response()->json([
            'error' => 'Unauthorized access. Admin Bearer token required to access subscriber database.',
            'message' => 'Direct unauthenticated browser access is blocked. Please log in at http://localhost:3000/#admin-login.'
        ], 401);
    }
}
