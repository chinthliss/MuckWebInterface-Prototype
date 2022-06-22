<?php


namespace App;

use App\User as User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class HostLogManager
{

    public function logHost(string $ip, ?User $user): void
    {
        if (!$user) return;
        //Not ideal but we don't want to log proxy entries in production and in testing everything comes from localhost
        if (App::environment() === 'production' && $ip === '127.0.0.1') return;

        // Have to check the table exists because it might not during testing
        if (Schema::hasTable('log_hosts')) {
            $character = $user->getCharacter();
            $hostname = gethostbyaddr($ip);
            DB::table('log_hosts')->updateOrInsert(
                [
                    'host_ip' => $ip,
                    'aid' => $user->getAid(),
                    'plyr_ref' => $character ? $character->dbref() : -1, // To match existing format
                    'game_code' => config('muck.muck_code')
                ], [
                    'host_name' => $hostname,
                    'plyr_name' => $character?->name(),
                    'plyr_tstamp' => $character?->createdTimestamp()->timestamp,
                    'tstamp' => Carbon::now()->timestamp
                ]
            );
        }
    }
}
