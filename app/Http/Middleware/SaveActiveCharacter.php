<?php

namespace App\Http\Middleware;

use App\Muck\MuckConnection;
use Closure;

/**
 * Class SaveActiveCharacter
 * Saves active character to the cookie so it can be resumed between sessions and navigation
 */
class SaveActiveCharacter
{

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $user = $request->user('account');
        if ($user && $characterDbref = $user->getCharacterDbref()) {
            return $response->withCookie(cookie()->forever('character-dbref', $characterDbref));
        }
        else {
            return $response;
        }
    }
}
