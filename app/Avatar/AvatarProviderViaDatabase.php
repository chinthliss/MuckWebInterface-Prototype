<?php

namespace App\Avatar;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AvatarProviderViaDatabase implements AvatarProvider
{
    private function databaseRowToAvatarGradient($row): AvatarGradient
    {
        return new AvatarGradient(
            $row->name,
            $row->description,
            json_decode($row->steps_json),
            $row->free ?: false,
            $row->created_at ? new Carbon($row->created_at) : null,
            $row->owner_aid ? User::find($row->owner_aid) : null
        );
    }

    public function getGradients(): array
    {
        $gradients = [];
        $rows = DB::table('avatar_gradients')
            ->get();
        foreach ($rows as $row) {
            $gradients[] = $this->databaseRowToAvatarGradient($row);
        }
        return $gradients;
    }

    public function getGradient(string $name): AvatarGradient
    {
        $gradient = null;
        $row = DB::table('avatar_gradients')
            ->where('name', '=', $name)
            ->first();
        if ($row) {
            $gradient = $this->databaseRowToAvatarGradient($row);
        }
        return $gradient;
    }
}
