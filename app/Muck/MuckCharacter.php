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
    private bool $wizard = false;

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
        if (in_array('wizard', $flags)) $this->wizard = true;
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
        return $this->wizard;
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
}
