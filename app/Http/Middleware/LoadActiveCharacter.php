<?php

namespace App\Http\Middleware;

use App\Muck\MuckCharacter;
use App\Muck\MuckObjectService;
use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
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
    protected $muckObjectService;

    /**
     * Requests in this list won't attempt to load a character.
     * This is intended to avoid the work if the request is just for a resource, such as an image
     * @var array
     */
    private array $routesExempt = [
        'avatar.gradient.image',
        'admin.avatar.dollthumbnail'
    ];

    public function __construct(MuckObjectService $muckObjectService)
    {
        $this->muckObjectService = $muckObjectService;
    }

    /**
     * If a character dbref is specified, verifies and sets active character on the User object
     * Takes it from the header or cookie with the former getting precedence.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //Allow bypass for pages that don't need a character
        if (in_array($request->route()?->getName(), $this->routesExempt)) return $next($request);

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
                $character = $this->muckObjectService->getByDbref($characterDbref);
                if ($character && $character->aid() == $user->getAid()) {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - accepted as $character");
                    $user->setCharacter($character);
                    // For the future? Add a context to all related log calls
                    // Log::withContext(['character' => $character->getName()];
                } else {
                    Log::debug("MultiplayerCharacter requested $characterDbref for $user - rejected, clearing cookie.");
                    Cookie::queue(Cookie::forget('character-dbref'));
                }
            }
        }
        return $next($request);
    }
}
