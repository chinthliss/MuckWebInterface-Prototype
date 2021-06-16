<?php


namespace App;


use App\Muck\MuckConnection;
use App\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
    public function getNotificationsFor(User $user)
    {
        $characters = $user->getCharacters();
        $query = $this->storageTable()
            ->where('aid', '=', $user->getAid())
            ->where(function ($query) {
                $query->where('game_code', '=', config('muck.muck_code'))
                    ->orWhereNull('game_code');
            });
        $rows = $query->get()->toArray();
        $query->update(['read_at' => Carbon::now()]);
        $result = ['user' => [], 'character' => []];
        foreach ($rows as $row) {
            if (!$row->game_code || !$row->character_dbref)
                array_push($result['user'], $row);
            else {
                $character = $characters->has($row->character_dbref) ? $characters[$row->character_dbref] : null;
                $character_name = $character ? $character->getName() : 'Unknown';
                if (!array_key_exists($character_name, $result['character'])) $result['character'][$character_name] = [];
                array_push($result['character'][$character_name], $row);
            }
        }
        return $result;
    }

    public function getNotification(int $id)
    {
        return $this->storageTable()->where('id', '=', $id)->first();
    }

    public function deleteNotification($id)
    {
        $this->storageTable()->delete($id);
    }

    public function deleteAllNotifications($accountId)
    {
        $this->storageTable()->where('aid', '=', $accountId)->delete();
    }

    public function getNotificationCountFor(User $user)
    {
        return $this->storageTable()
            ->where('aid', '=', $user->getAid())
            ->count();
    }
}
