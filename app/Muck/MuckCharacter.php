<?php


namespace App\Muck;

use App\Contracts\MuckConnection;

/**
 * Class MuckCharacter
 * This is largely an empty stub to pass to the client - at which point the client will query for more info as appropriate
 * @package App\Muck
 */
class MuckCharacter
{
    private $name;
    private $dbref;
    private $flags;
    private $level;

    public function __construct(int $dbref, string $name, int $level = null, array $flags = [])
    {
        $this->dbref = $dbref;
        $this->name = $name;
        $this->level = $level;
        $this->flags = $flags;
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

    public static function fromMuckResponse(string $muckResponse)
    {
        $parts = explode(',', $muckResponse);
        if (count($parts) !== 5) throw new \InvalidArgumentException("Muck response should contain 4 parts.");
        list($dbref, $characterName, $level, $avatar, $flagsAsString) = $parts;
        $flags = [];
        if ($flagsAsString) {
        }
        return new self($dbref, $characterName, $level, $flags);
    }
}
