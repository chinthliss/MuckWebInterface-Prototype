<?php


namespace App\Muck;

use Illuminate\Support\Carbon;

/**
 * Utility class to represent a loaded muck dbRef.
 */
class MuckDbref
{
    public static array $typeFlags = [
        'P' => 'player',
        'Z' => 'zombie',
        'R' => 'room',
        'T' => 'thing'
    ];

    protected int $dbref;

    protected string $name;

    protected string $typeFlag;

    /**
     * @var Carbon|null The created timestamp - in conjunction with the dbref acts as a signature since dbrefs can be reused
     */
    protected Carbon $createdTimestamp;

    /**
     * @var int|null This object's reference in the Muck Object table, if loaded
     */
    protected ?int $muckObjectId = null;

    /**
     * @param int $dbref
     * @param string $name
     * @param string $typeFlag
     */
    public function __construct(int $dbref, string $name, string $typeFlag, Carbon $createdTimestamp)
    {
        if (!array_key_exists($typeFlag, self::$typeFlags)) {
            throw new \Error('Unrecognized type flag specified: ' . $typeFlag);
        }

        $this->dbref = $dbref;
        $this->createdTimestamp = $createdTimestamp;
        $this->name = $name;
        $this->typeFlag = $typeFlag;
    }

    public function __toString(): string
    {
        return $this->name . '(#' . $this->dbref . $this->typeFlag . ')';
    }

    public function dbref(): int
    {
        return $this->dbref;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function createdTimestamp() : Carbon
    {
        return $this->createdTimestamp;
    }

    public function toInt()
    {
        return $this->dbref;
    }

}
