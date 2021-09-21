<?php


namespace App;

use App\User as User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class HostLogManager
{

    public function logHost(string $ip, string $hostName, ?User $user)
    {
        // Have to check the table exists because it might not during testing
        if (Schema::hasTable('log_hosts')) {

            $character = $user ? $user->getCharacter() : null;
            DB::table('log_hosts')->updateOrInsert(
                [
                    'host_ip' => $ip,
                    'aid' => $user ? $user->getAid() : 0, // To match existing format
                    'plyr_ref' => $character ? $character->dbref() : -1, // To match existing format
                    'muckname' => config('muck.muck_name')
                ], [
                    'host_name' => $hostName,
                    'plyr_name' => $character ? $character->name() : '', // To match existing format
                    'tstamp' => Carbon::now()->timestamp
                ]
            );
        }
    }
}
