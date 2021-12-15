<?php

namespace App\Http\Middleware;

use App\Muck\MuckObjectService;
use App\User;
use Closure;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyFromMuck
{
    private MuckObjectService $muckObjectService;
    private string $salt;

    public function __construct(MuckObjectService $muckObjectService)
    {
        $this->muckObjectService = $muckObjectService;
        $salt = config('muck.salt');
        if (!$salt) throw new Error("Salt hasn't been set in Muck connection config. Ensure MUCK_SALT is set.");
        $this->salt = $salt;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        Log::debug("Verifying request from Muck - $request");

        $signature = $request->header('Signature');
        if (!$signature) {
            Log::warning("A request to the Muck API came in without a signature:\n$request");
            abort(403);
        }

        $content = $request->getContent();
        $calculatedSignature =  sha1($content . $this->salt);
        Log::debug("TESTING Content: " . $content);
        Log::debug("TESTING Signature: " . $signature);
        Log::debug("TESTING Calculated: " . $calculatedSignature);
        if ($signature !== $calculatedSignature) {
            Log::warning("A request to the Muck API came in with an incorrect signature (Expected: '$calculatedSignature'):\n$request");
            abort(403);
        }

        $user = null;

        if ($request->has('mwi_dbref')) {
            $character = $this->muckObjectService->getByDbref($request->get('mwi_dbref'));
            if (!$character || !$character->isPlayer()) abort(400);
            $user = User::find($character->aid());
        }

        if ($request->has('mwi_user')) {
            $user = User::find($request->get('mwi_user'));
            if (!$user) abort(400);
        }

        if ($user) {
            auth()->setUser($user);
            if ($character) $user->setCharacter($character);
        }

        return $next($request);
    }
}
