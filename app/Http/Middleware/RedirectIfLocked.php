<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

// Redirects an account to the locked page if it's locked.
class RedirectIfLocked
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) abort(500, "User should have been set before this call");

        if ($user->lockedAt)
            return redirect()->route('auth.account.locked');

        return $next($request);
    }

}
