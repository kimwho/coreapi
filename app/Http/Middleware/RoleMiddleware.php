<?php

// app/Http/Middleware/RoleMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  array  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        
        \Log::info('User ID: ' . ($user ? $user->id : 'No user'));
        \Log::info('User Roles: ' . ($user ? implode(', ', $user->getRoleNames()->toArray()) : 'No roles'));
        \Log::info('Checking for roles: ' . implode(', ', $roles));
        \Log::info('Request URI: ' . $request->getRequestUri());

        if (!$user) {
            \Log::info('No authenticated user.');
            return response()->json(['error' => 'Unauthorized. No authenticated user.'], 403);
        }

        // Check if the user has any of the specified roles
        $hasAnyRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasAnyRole = true;
                break;
            }
        }

        if (!$hasAnyRole) {
            \Log::info('User does not have any of the required roles.');
            return response()->json(['error' => 'Unauthorized. You do not have permission to perform this action.'], 403);
        }

        \Log::info('User has one of the required roles.');
        return $next($request);
    }
}



