<?php

namespace App\Muck;

interface MuckObjectsProvider
{
    public function getById(int $id) : MuckDbref;

    public function getIdFor(MuckDbref $muckDbref) : int;
}
