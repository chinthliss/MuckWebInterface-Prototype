<?php

namespace App\SupportTickets;

use App\Muck\MuckDbref;
use App\User;
use Illuminate\Support\Carbon;

class SupportTicket
{
    public int $id;
    public string $category;
    public string $title;
    public ?User $user;
    public ?MuckDbref $character;
    public Carbon $createdAt;
    public Carbon $updatedAt;
    public string $status;
    public Carbon $statusAt;
    public ?string $closureReason;
    public ?Carbon $closedAt;
    public bool $isPublic;
    public string $content;

    /**
     * Use the createNew and createExisting static functions instead
     * @param int $id
     * @param string $category
     * @param string $title
     * @param string $content
     * @param User|null $user
     * @param MuckDbref|null $character
     */
    private function __construct(
        int        $id,
        string     $category,
        string     $title,
        string     $content,
        ?User      $user,
        ?MuckDbref $character
    )
    {
        $this->id = $id;
        $this->category = $category;
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
        $this->character = $character;
    }

    public static function createNew(
        int        $id,
        string     $category,
        string     $title,
        string     $content,
        ?User      $user,
        ?MuckDbref $character
    ): SupportTicket
    {
        $ticket = new self(
            $id,
            $category,
            $title,
            $content,
            $user,
            $character
        );

        //Defaults for new ticket
        $ticket->createdAt = Carbon::now();
        $ticket->status = 'new';
        $ticket->statusAt = Carbon::now();
        $ticket->closedAt = null;
        $ticket->closureReason = null;
        $ticket->isPublic = false;
        $ticket->updatedAt = Carbon::now();
        return $ticket;
    }

    public static function createExisting(
        int        $id,
        string     $category,
        string     $title,
        ?User      $user,
        ?MuckDbref $character,
        Carbon     $createdAt,
        string     $status,
        Carbon     $statusAt,
        Carbon     $updatedAt,
        ?string    $closureReason,
        ?Carbon    $closedAt,
        bool       $isPublic,
        string     $content
    ): SupportTicket
    {
        $ticket = new self(
            $id,
            $category,
            $title,
            $content,
            $user,
            $character
        );

        $ticket->createdAt = $createdAt;
        $ticket->status = $status;
        $ticket->statusAt = $statusAt;
        $ticket->updatedAt = $updatedAt;
        $ticket->closedAt = $closedAt;
        $ticket->closureReason = $closureReason;
        $ticket->isPublic = $isPublic;
        return $ticket;
    }

    public function __toString(): string
    {
        return "SupportTicket#$this->id[$this->category/$this->title]";
    }

    /**
     * Returns an array
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'createdAt' => $this->createdAt,
            'statusAt' => $this->statusAt,
            'status' => $this->status,
            'closedAt' => $this->closedAt,
            'closureReason' => $this->closureReason,
            'isPublic' => $this->isPublic
        ];
        if ($this->user) $array['user'] = $this->user->getAid();
        if ($this->character) $array['character'] = $this->character->name();
        return $array;
    }
}
