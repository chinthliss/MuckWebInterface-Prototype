<?php

namespace App\SupportTickets;

use App\Muck\MuckDbref;
use App\User;
use Illuminate\Support\Carbon;

class SupportTicketLog
{
    public Carbon $when;
    public string $type;
    public bool $staffOnly;
    public string $content;
    public ?User $user;
    public ?MuckDbref $character;

    public function __construct(
        Carbon     $when,
        string     $type,
        bool       $staffOnly,
        string     $content,
        ?User      $user = null,
        ?MuckDbref $character = null
    )
    {
        $this->when = $when;
        $this->type = $type;
        $this->staffOnly = $staffOnly;
        $this->content = $content;
        $this->user = $user;
        $this->character = $character;
    }

    public function toArray(): array
    {
        $array = [
            'when' => $this->when,
            'type' => $this->type,
            'staffOnly' => $this->staffOnly,
            'content' => $this->content
        ];
        if ($this->user) $array['user'] = $this->user->getAid();
        if ($this->character) $array['character'] = $this->character->name();
        return $array;
    }
}
