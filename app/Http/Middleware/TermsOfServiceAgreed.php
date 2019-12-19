<?php

namespace App\Http\Middleware;

use Closure;

class TermsOfServiceAgreed
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        //Because this should have been checked by an earlier middleware
        if (!$user) abort(500, 'Server error - Terms of Service check called with no user set.');
        if (!$user->getAgreedToTermsofService())
            return redirect()->route('auth.account.termsofservice');
        return $next($request);
    }
}
