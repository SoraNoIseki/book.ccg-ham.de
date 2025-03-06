<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleWildcard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $rolePattern
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $rolePattern)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('login');
        }

        // Check if the user has any role that matches the pattern
        $hasRole = $user->roles->contains(function ($role) use ($rolePattern) {
            return fnmatch($rolePattern, $role->internal_name);
        });

        if (!$hasRole) {
            return response()->view('errors.401', [], 401);
        }

        return $next($request);
    }
}