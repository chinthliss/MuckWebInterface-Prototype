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
        if (!$user || !$user->hasRole($role)) {
            abort(403);
        }
        return $next($request);
    }

}
