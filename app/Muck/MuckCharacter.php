<?php


namespace App\Muck;

/**
 * Class MuckCharacter
 * Builds on MuckDbref to add unique character details
 * This is largely an empty stub to pass to the client - at which point the client will query for more info as appropriate
 * @package App\Muck
 */
class MuckCharacter extends MuckDbref
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $wizard = false;

    /**
     * @var int|null
     */
    private $level;

    public function __construct(int $dbref, string $name, int $level = null, array $flags = [])
    {
        parent::__construct($dbref);
        $this->name = $name;
        $this->level = $level;
        if (in_array('wizard', $flags)) $this->wizard = true;
    }

    public function getDbref(): int
    {
        return $this->toInt();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'dbref' => $this->dbref,
            'name' => $this->name,
            'level' => $this->level,
            'wizard' => $this->wizard
        ];
    }

    public static function fromMuckResponse(string $muckResponse): MuckCharacter
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
