<?php


namespace App;

use App\User as User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccountNotificationManager
{

    /**
     * @return Builder
     */
    private function storageTable(): Builder
    {
        return DB::table('account_notifications');
    }

    /**
     * @param User $user
     * @return array
     * Returned in the form { user:[notification], character:[character_name:[notification]]
     */
    public function getNotificationsFor(User $user): array
    {
        $characters = $user->getCharacters();
        $query = $this->storageTable()
            ->where('aid', '=', $user->getAid())
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->orderByDesc('created_at');
        $rows = $query->get()->toArray();
        $query->update(['read_at' => Carbon::now()]);
        $result = ['user' => [], 'character' => []];
        foreach ($rows as $row) {
            if (!$row->game_code || !$row->character_dbref)
                array_push($result['user'], $row);
            else {
                $character = array_key_exists($row->character_dbref, $characters) ? $characters[$row->character_dbref] : null;
                $character_name = $character ? $character->getName() : 'Unknown';
                if (!array_key_exists($character_name, $result['character'])) $result['character'][$character_name] = [];
                array_push($result['character'][$character_name], $row);
            }
        }
        Log::debug("Account Notifications - getNotificationsFor Account#{$user->getAid()} found " . count($result['user']) . " user"
            . " and " . count($result['character']) . " character notifications.");
        return $result;
    }

    public function getNotification(int $id) : object
    {
        return $this->storageTable()->where('id', '=', $id)->first();
    }

    public function deleteNotification($id)
    {
        $this->storageTable()->delete($id);
    }

    public function deleteAllNotificationsForAccount($accountId)
    {
        $this->storageTable()
            ->where('aid', '=', $accountId)
            ->whereNull('character_dbref')
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->delete();
    }

    public function deleteAllNotificationsForCharacterDbref($accountId, $dbref)
    {
        $this->storageTable()
            ->where('aid', '=', $accountId)
            ->where('game_code', '=', config('muck.muck_code'))
            ->where('character_dbref', '=', $dbref)
            ->delete();
    }


    public function getNotificationCountFor(User $user): int
    {
        $count = $this->storageTable()
            ->where('aid', '=', $user->getAid())
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
            ->count();
        Log::debug("AccountNotifications - getNotificationCountFor Account#{$user->getAid()} = $count");
        return $count;
    }
}
