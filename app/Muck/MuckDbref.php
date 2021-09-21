<?php


namespace App\Muck;

/**
 * Utility class to represent a muck dbRef and allow type inference.
 */
class MuckDbref
{
    protected int $dbref;

    protected string $name;

    protected string $typeFlag;

    /**
     * @param int|string $dbref
     */
    public function __construct($dbref, $name, $typeFlag)
    {
        $this->dbref = $dbref;
        $this->name = $name;
        $this->typeFlag = $typeFlag;
    }

    public function __toString(): string
    {
        return $this->name . '(#' . $this->dbref . $this->typeFlag . ')';
    }


    public function dbref(): int
    {
        return $this->toInt();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toInt()
    {
        return $this->dbref;
    }

}
