<?php

namespace App\Http\Middleware;

use App\Muck\MuckConnection;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Class LoadActiveCharacter
 * Verifies and sets the active character. Does not gate on such.
 */
class LoadActiveCharacter
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
     * If a character dbref is specified, verifies and sets active character on the User object
     * Takes it from the header or cookie with the former getting precedence.
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
                Log::debug("MultiplayerCharacter requested dbref: {$characterDbref}");
                $character = $this->muck->retrieveAndVerifyCharacterOnAccount($user, $characterDbref);
                if ($character) $user->setCharacter($character);
            }
        }
        return $next($request);
    }
}
