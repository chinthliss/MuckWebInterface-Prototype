<?php


namespace App\Muck;

use Illuminate\Support\Carbon;

/**
 * Utility class to represent a loaded muck dbRef.
 */
class MuckDbref
{
    public static array $typeFlags = [
        'p' => 'player',
        'z' => 'zombie',
        'r' => 'room',
        't' => 'thing'
    ];

    protected int $dbref;

    protected string $name;

    protected string $typeFlag;

    /**
     * @var Carbon The created timestamp - in conjunction with the dbref acts as a signature since dbrefs can be reused
     */
    protected Carbon $createdTimestamp;

    /**
     * @param int $dbref
     * @param string $name
     * @param string $typeFlag
     * @param Carbon $createdTimestamp
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

    public function toInt() : int
    {
        return $this->dbref;
    }

    public function typeFlag() : string
    {
        return $this->typeFlag;
    }

    /**
     * Utility class to check if this is a player
     * @return bool
     */
    public function isPlayer(): bool
    {
        return $this->typeFlag == 'p';
    }

    public function toArray() : array
    {
        return [
            'dbref' => $this->dbref,
            'type' => $this->typeFlag,
            'name' => $this->name,
            'created' => $this->createdTimestamp
        ];
    }

}
