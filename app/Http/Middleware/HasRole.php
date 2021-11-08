<?php

namespace App\Http\Middleware;

use App\User as User;
use Closure;
use Illuminate\Http\Request;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user || !$user->hasRole($role)) { // hasRole deals with special exceptions
            abort(403);
        }
        return $next($request);
    }

}
