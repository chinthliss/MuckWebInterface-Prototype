<?php

namespace App\Avatar;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AvatarProviderViaDatabase implements AvatarProvider
{

    #region Gradients

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

    public function getGradient(string $name): ?AvatarGradient
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

    #endregion Gradients

    #region Items

    private function databaseRowToAvatarItem($row): AvatarItem
    {
        return new AvatarItem(
            $row->id,
            $row->name,
            $row->filename,
            $row->type,
            $row->requirement,
            $row->created_at ? new Carbon($row->created_at) : null,
            $row->owner_aid ? User::find($row->owner_aid) : null,
            $row->cost,
            $row->x,
            $row->y,
            $row->rotate,
            $row->scale
        );
    }

    public function getItems(): array
    {
        $items = [];
        $rows = DB::table('avatar_items')
            ->get();
        foreach ($rows as $row) {
            $items[] = $this->databaseRowToAvatarItem($row);
        }
        return $items;
    }

    public function getItem(string $itemName): ?AvatarItem
    {
        $item = null;
        $row = DB::table('avatar_items')
            ->where('name', '=', $itemName)
            ->first();
        if ($row) {
            $item = $this->databaseRowToAvatarItem($row);
        }
        return $item;
    }

    #endregion Items
}
