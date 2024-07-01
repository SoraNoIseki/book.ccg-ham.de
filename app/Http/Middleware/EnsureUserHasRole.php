<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // check user login
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if (!$user->hasRole($role)) {
            return response()->view('errors.401', [], 401);
        }

        return $next($request);
    }
}