<?php

namespace App\Http\Middleware;

use App\Muck\MuckCharacter;
use App\Muck\MuckObjectService;
use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class LoadActiveCharacter
 * Verifies and sets the active character. Does not gate on such.
 */
class LoadActiveCharacter
{
    /**
     * @var MuckObjectService
     */
    protected $muck;

    public function __construct(MuckObjectService $muck)
    {
        $this->muck = $muck;
    }

    /**
     * If a character dbref is specified, verifies and sets active character on the User object
     * Takes it from the header or cookie with the former getting precedence.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $clearCookie = false;

        /** @var User $user */
        $user = $request->user('account');
        if ($user) {
            // Header is the definitive place to have the character specified
            $characterDbref = $request->header('X-Character-Dbref');

            // But the cookie is used on initial set and to keep *something* between sessions
            if (!$characterDbref) $characterDbref = $request->cookie('character-dbref');

            $characterDbref = intval($characterDbref);
            if ($characterDbref) {
                /** @var MuckCharacter $character */
                $character = $this->muck->getByDbref($characterDbref);
                if ($character && $character->aid() == $user->getAid()) {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - accepted as $character");
                    $user->setCharacter($character);
                    // For the future? Add a context to all related log calls
                    // Log::withContext(['character' => $character->getName()];
                }
                else {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - rejected, clearing cookie.");
                    $clearCookie = true;
                }
            }
        }
        $response = $next($request);

        if ($clearCookie && method_exists($response, 'withCookie'))
            return $response->withCookie(cookie()->forget('character-dbref'));
        else
            return $response;
    }
}
