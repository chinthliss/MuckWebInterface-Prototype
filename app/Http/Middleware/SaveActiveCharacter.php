<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class SaveActiveCharacter
 * Saves active character to the cookie so it can be resumed between sessions and navigation
 */
class SaveActiveCharacter
{

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user('account');
        // Some responses, eg binary ones, don't have a withCookie
        if ($user && ($characterDbref = $user->getCharacterDbref()) && method_exists($response, 'withCookie')) {
            Log::debug("Saving cookie for active character on User {$user->getAid()} to: {$characterDbref}");
            return $response->withCookie(cookie()->forever('character-dbref', $characterDbref));
        } else {
            return $response;
        }
    }
}
