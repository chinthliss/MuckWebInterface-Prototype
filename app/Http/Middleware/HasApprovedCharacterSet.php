<?php

namespace App\Http\Middleware;

use Closure;
use App\User as User;
use Illuminate\Http\Request;

class HasApprovedCharacterSet
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

        $character = $user->getCharacter();
        if (!$character) {
            if (!$request->expectsJson()) {
                session()->flash('message-success', 'You need to select or create a character to continue.');
                redirect()->setIntendedUrl($request->getRequestUri());
                return redirect(route('multiplayer.character.select'));
            }
            abort(400, "Active character hasn't been set or specified correctly.");
        }

        if (!$character->isApproved()) {
            if (!$request->expectsJson()) {
                session()->flash('message-success', 'You need to complete character generation to continue.');
                redirect()->setIntendedUrl($request->getRequestUri());
                return redirect(route('multiplayer.character.generate'));
            }
            abort(400, "Active character hasn't gone through character generation.");
        }

        return $next($request);
    }

}
