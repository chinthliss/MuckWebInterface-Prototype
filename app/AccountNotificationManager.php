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
     * @return array Array of [character, created_at, read_at, message]
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

        $result = [];
        foreach ($rows as $row) {
            $character = array_key_exists($row->character_dbref, $characters) ? $characters[$row->character_dbref] : null;
            $result[] = [
                'id' => $row->id,
                'character' => $character,
                'created_at' => $row->created_at,
                'read_at' => $row->read_at,
                'message' => $row->message
            ];
        }
        Log::debug("Account Notifications - getNotificationsFor Account#{$user->getAid()}: " . count($result));
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

    public function deleteAllNotificationsFor($accountId)
    {
        $this->storageTable()
            ->where('aid', '=', $accountId)
            ->where(function ($query) {
                $query->whereNull('game_code')
                    ->orWhere('game_code', '=', config('muck.muck_code'));
            })
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
