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
     * @var bool
     */
    private $wizard = false;

    /**
     * @var bool
     */
    private $approved = true;

    /**
     * @var int|null
     */
    private $level;

    public function __construct(int $dbref, string $name, int $level = null, array $flags = [])
    {
        parent::__construct($dbref, $name, 'P');
        $this->level = $level;
        if (in_array('unapproved', $flags)) $this->approved = false;
        if (in_array('wizard', $flags)) $this->wizard = true;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function toArray(): array
    {
        return [
            'dbref' => $this->dbref,
            'name' => $this->name,
            'level' => $this->level,
            'approved' => $this->approved,
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
