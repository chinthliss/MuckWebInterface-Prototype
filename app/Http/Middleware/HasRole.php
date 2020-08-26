<?php

namespace App\Http\Middleware;

use Closure;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle($request, Closure $next, string $role)
    {
        $user = $request->user();
        //Because this should have been checked by an earlier middleware
        if (!$user) abort(500, 'Server error - HasRole checked with no user set.');
        if (!$user->hasRole($role)) {
            abort(403);
        }
        return $next($request);
    }

}
