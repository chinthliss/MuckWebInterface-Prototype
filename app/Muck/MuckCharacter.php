<?php


namespace App\Muck;

use App\Contracts\MuckConnectionContract;

/**
 * Class MuckCharacter
 * This is largely an empty stub to pass to the client - at which point the client will query for more info as appropriate
 * @package App\Muck
 */
class MuckCharacter
{
    private $name;
    private $dbref;

    public function __construct(int $dbref, string $name)
    {
        $this->dbref = $dbref;
        $this->name = $name;
    }

    public function getDbref()
    {
        return $this->dbref;
    }

    public function getName()
    {
        return $this->name;
    }

    public function toArray()
    {
        return [
            'dbref' => $this->dbref,
            'name' => $this->name
        ];
    }
}
