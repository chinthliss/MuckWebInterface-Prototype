<?php


namespace App\Muck;

use Illuminate\Support\Carbon;

/**
 * Class MuckCharacter
 * Builds on MuckDbref to add unique character details for either a player object or NPC zombie
 * @package App\Muck
 */
class MuckCharacter extends MuckDbref
{
    /**
     * Wiz level, maps as follows from muck:
     * No wiz level      - 0
     * Wiz level 1 and 2 - 1 - Staff on web
     * Higher wiz levels - 2 - Admin on web
     * @var int
     */
    private int $wizLevel = 0;

    private bool $approved = true;

    private ?int $level;

    // Null if zombie
    private ?int $accountId;

    public function __construct(int $dbref, string $name, Carbon $createdTimestamp,
                                int $level = null, string $avatar = null, array $flags = [], int $accountId = null)
    {
        parent::__construct($dbref, $name, $accountId ? 'p' : 'z', $createdTimestamp);
        $this->level = $level;
        $this->accountId = $accountId;
        if (in_array('unapproved', $flags)) $this->approved = false;
        if (in_array('staff', $flags)) $this->wizLevel = 1;
        if (in_array('admin', $flags)) $this->wizLevel = 2;
    }

    public function aid(): ?int
    {
        return $this->accountId;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function isStaff(): bool
    {
        return $this->wizLevel > 0;
    }

    public function isAdmin(): bool
    {
        return $this->wizLevel > 1;
    }

    public function toArray(): array
    {
        $array = [
            'dbref' => $this->dbref,
            'name' => $this->name,
            'level' => $this->level,
        ];
        if (!$this->approved) $array['unapproved'] = true;
        if ($this->wizLevel) $array['wizLevel'] = $this->wizLevel;
        return $array;
    }
}
