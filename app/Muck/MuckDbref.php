<?php


namespace App\Muck;

/**
 * Utility class to represent a muck dbRef and allow type inference.
 */
class MuckDbref
{
    /**
     * @var int
     */
    protected $dbref;

    /**
     * @param int|string $dbref
     */
    public function __construct($dbref)
    {
        $this->dbref = $dbref;
    }

    public function toInt()
    {
        return $this->dbref;
    }
}
