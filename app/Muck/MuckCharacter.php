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
    private $wizard = false;
    private $level;

    public function __construct(int $dbref, string $name, int $level = null, array $flags = [])
    {
        $this->dbref = $dbref;
        $this->name = $name;
        $this->level = $level;
        if (in_array('wizard', $flags)) $this->wizard = true;
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
            'name' => $this->name,
            'level' => $this->level,
            'wizard' => $this->wizard
        ];
    }

    public static function fromMuckResponse(string $muckResponse)
    {
        $parts = explode(',', $muckResponse);
        if (count($parts) !== 5)
            throw new \InvalidArgumentException("Muck response contains the wrong number of parts");
        list($dbref, $characterName, $level, $avatar, $flagsAsString) = $parts;
        $flags = [];
        if ($flagsAsString) {
            $flags = explode(':', $flagsAsString);
        }
        return new self($dbref, $characterName, $level, $flags);
    }
}
