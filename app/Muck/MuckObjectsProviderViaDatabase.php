<?php

namespace App\Muck;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MuckObjectsProviderViaDatabase implements MuckObjectsProvider
{

    /**
     * @inheritDoc
     */
    public function getById(int $id): ?array
    {
        Log::debug("MuckObjectDB - Retrieving object with the id of $id");
        $row = DB::table('muck_objects')
            ->where('id', '=', $id)
            ->first();

        if (!$row) return null;
        return [
            'dbref' => $row->dbref,
            'created' => new Carbon($row->created_at),
            'name' => $row->name,
            'deleted' => ($row->deleted_at != null)
        ];
    }

    /**
     * Retrieves or creates the associated MuckObjectId for a given dbref
     * @param MuckDbref $muckDbref
     * @return int
     */
    public function getIdFor(MuckDbref $muckDbref): int
    {
        Log::debug("MuckObjectDB - Fetching ID for $muckDbref");

        // Try to find it first
        $row = DB::table('muck_objects')
            ->where('game_code', '=', config('muck.muck_code'))
            ->where('dbref', '=', $muckDbref->dbref())
            ->where('created_at', '=', $muckDbref->createdTimestamp())
            ->first();
        if ($row) {
            Log::debug("MuckObjectDB - Found existing ID of $row->id for $muckDbref");
            return $row->id;
        }

        //Otherwise create an entry
        $type = 'thing';
        switch ($muckDbref->typeFlag()) {
            case 'P':
                $type = 'player';
                break;
            case 'Z':
                $type = 'zombie';
                break;
            case 'R':
                $type = 'room';
                break;
        }
        $databaseArray = [
            'game_code' => config('muck.muck_code'),
            'dbref' => $muckDbref->dbref(),
            'created_at' => $muckDbref->createdTimestamp(),
            'type' => $type,
            'name' => $muckDbref->name()
        ];

        $id = DB::table('muck_objects')
            ->insertGetId($databaseArray);
        Log::debug("MuckObjectDB - Created new ID of $id for $muckDbref");
        return $id;
    }

    public function removeById(int $id)
    {
        Log::debug("MuckObjectDB - Remove request for ID: $id");
        $row = DB::table('muck_objects')
            ->where('id', '=', $id)
            ->first();
        if ($row) {
            if ($row->type == 'player') {
                DB::table('muck_objects')
                    ->where('id', '=', $id)
                    ->update(['deleted_at' => Carbon::now()]);
                Log::debug("MuckObjectDB - Flagged player entry as deleted, ID: $id");
            } else {
                DB::table('muck_objects')
                    ->where('id', '=', $id)
                    ->delete();
                Log::debug("MuckObjectDB - Deleted row with ID: $id");
            }
        }
    }

    public function updateName(int $id, string $name)
    {
        Log::debug("MuckObjectDB - Updating name for $id to: $name");
        DB::table('muck_objects')
            ->where('id', '=', $id)
            ->update(['name' => $name]);
    }
}
