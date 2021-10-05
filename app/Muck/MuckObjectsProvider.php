<?php

namespace App\Muck;

use JetBrains\PhpStorm\ArrayShape;

interface MuckObjectsProvider
{
    /**
     * Returns the details to allow the MuckObjectService to retrieve a validated dbref
     * @param int $id
     * @return null|array
     */
    #[ArrayShape([
        'dbref' => 'int',
        'created' => 'Illuminate\Support\Carbon::class',
        'name' => 'string',
        'deleted' => 'bool'
    ])]
    public function getById(int $id) : ?array;

    /**
     * Finds or creates the ID for the given dbref
     * @param MuckDbref $muckDbref
     * @return int
     */
    public function getIdFor(MuckDbref $muckDbref) : int;

    /**
     * If the object is a player, it's flagged as deleted, otherwise it's actually deleted
     * @param int $id
     * @return mixed
     */
    public function removeById(int $id);

    /**
     * Update the name - since it's the only thing that can change once a record is added
     * @param int $id
     * @param string $name
     */
    public function updateName(int $id, string $name);
}
