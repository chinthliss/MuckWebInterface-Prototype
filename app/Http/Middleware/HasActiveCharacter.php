<?php

namespace App\Http\Middleware;

use Closure;

class HasActiveCharacter
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->getCharacter()) {
            if (!$request->expectsJson()) {
                session()->flash('message-success', 'You need to select or create a character to continue.');
                redirect()->setIntendedUrl($request->getRequestUri());
                return redirect(route('multiplayer.character.select'));
            }
            abort(400, "Active character hasn't been set correctly.");

        }
        return $next($request);
    }

}
