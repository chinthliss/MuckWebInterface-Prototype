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
            'whenTimespan' => $this->when->diffForHumans(),
            'type' => $this->type,
            'staffOnly' => $this->staffOnly,
            'content' => $this->content
        ];
        if ($this->character) {
            $array['character'] = $this->character->name();
        }
        return $array;
    }

    public function toAdminArray(): array
    {
        $array = $this->toArray();
        if ($this->user) {
            $array['user'] = $this->user->getAid();
            $array['user_url'] = $this->user->getAdminUrl();
        }
        if ($this->character) $array['characterDbref'] = $this->character->dbref();
        return $array;
    }

}
