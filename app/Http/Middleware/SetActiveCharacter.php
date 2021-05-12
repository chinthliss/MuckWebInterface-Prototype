<?php

namespace App\Http\Middleware;

use App\Muck\MuckConnection;
use Closure;

/**
 * Class SetActiveCharacter
 * Verifies and sets the active character. Does not gate on such, merely keeps the setting going.
 */
class SetActiveCharacter
{
    /**
     * @var MuckConnection
     */
    protected $muck;

    public function __construct(MuckConnection $muck)
    {
        $this->muck = $muck;
    }

    /**
     * During request  - If a character dbref is specified, verifies and sets active character on the User object
     *                   Takes it from the header or cookie with the former getting precedence.
     * During response - If an active character is set on User, adds it to the cookie
     *
     * TODO: Separate SetActiveCharacter out so that it can be used at the api level later,
     *   as otherwise setting the cookie causes issues.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user('account');
        if ($user) {
            // Header is the definitive place to have the character specified
            $characterDbref = $request->header('X-Character-Dbref');

            // But the cookie is used on initial set and to keep *something* between sessions
            if (!$characterDbref) $characterDbref = $request->cookie('character-dbref');

            $characterDbref = intval($characterDbref);
            if ($characterDbref) {
                $character = $this->muck->retrieveAndVerifyCharacterOnAccount($user, $characterDbref);
                if ($character) $user->setCharacter($character);
            }
        }
        $response = $next($request);

        //Re-fetch user just in case they weren't logged in beforehand and logged in directly as a character
        $user = $request->user('account');
        if ($user && $characterDbref = $user->getCharacterDbref()) {
            return $response->withCookie(cookie()->forever('character-dbref', $characterDbref));
        }
        else {
            return $response;
        }
    }
}
