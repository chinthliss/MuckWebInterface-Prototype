<?php

namespace App\Http\Middleware;

use App\HostLogManager;
use App\User as User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Saves an entry to the host table
 */
class LogHost
{

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /** @var User $user */
        $user = $request->user('account');

        /** @var HostLogManager $hostLog */
        $hostLog = resolve(HostLogManager::class);
        $hostLog->logHost($request->ip(), $user);

        return $response;
    }
}
