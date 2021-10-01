<?php

namespace App\Muck;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MuckObjectsProviderViaDatabase implements MuckObjectsProvider
{

    /**
     * Retrieves the dbref associated with the given MuckObjectId
     * @param int $id
     * @return MuckDbref
     */
    public function getById(int $id): MuckDbref
    {
        Log::debug("MuckObject - Retrieving object with the id of $id");
        $object = null;
        $row = DB::table('muck_objects')
            ->where('id', '=', $id)
            ->first();
        if ($row) {
            $typeFlag = 'T';
            switch ($row->type) {
                case 'player':
                    $typeFlag = 'P';
                    break;
                case 'zombie':
                    $typeFlag = 'Z';
                    break;
                case 'room':
                    $typeFlag = 'R';
                    break;
            }
            $object = new MuckDbref($row->dbref, $row->name, $typeFlag, $row->created_at);
        }

        Log::debug("MuckObject - Retrieved object: $object");
        return $object;
    }

    /**
     * Retrieves or creates the associated MuckObjectId for a given dbref
     * @param MuckDbref $muckDbref
     * @return int
     */
    public function getIdFor(MuckDbref $muckDbref): int
    {
        Log::debug("MuckObject - Fetching ID for $muckDbref");

        // Try to find it first
        $row = DB::table('muck_objects')
            ->where('game_code', '=', config('muck.muck_code'))
            ->where('dbref', '=', $muckDbref->dbref())
            ->where('created_at', '=', $muckDbref->createdTimestamp())
            ->first();
        if ($row) {
            Log::debug("MuckObject - Found existing ID of $row->id for $muckDbref");
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
        Log::debug("MuckObject - Created new ID of $id for $muckDbref");
        return $id;
    }
}
