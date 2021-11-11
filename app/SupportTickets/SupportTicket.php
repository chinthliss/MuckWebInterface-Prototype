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
    public ?User $fromUser;
    public ?MuckDbref $fromCharacter;
    public ?User $agentUser;
    public ?MuckDbref $agentCharacter;
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
        $this->fromUser = $user;
        $this->fromCharacter = $character;
    }

    public static function createNew(
        int        $id,
        string     $category,
        string     $title,
        string     $content,
        ?User      $fromUser,
        ?MuckDbref $fromCharacter
    ): SupportTicket
    {
        $ticket = new self(
            $id,
            $category,
            $title,
            $content,
            $fromUser,
            $fromCharacter
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
        ?User      $fromUser,
        ?MuckDbref $fromCharacter,
        ?User      $agentUser,
        ?MuckDbref $agentCharacter,
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
            $fromUser,
            $fromCharacter
        );
        $ticket->agentUser = $agentUser;
        $ticket->agentCharacter = $agentCharacter;
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

    public function serializeForAgentListing(SupportTicketService $service) : array
    {
        return [
            'id' => $this->id,
            'url' => route('support.agent.ticket', ['id' => $this->id]),
            'category' => $this->category,
            'title' => $this->title,
            'status' => $this->status,
            'lastUpdatedAt' => $this->updatedAt,
            'lastUpdatedAtTimespan' => $this->updatedAt->diffForHumans(),
            'isPublic' => $this->isPublic,
            'from' => [
                'user' => $this->fromUser->toAdminArray(),
                'character' => $this->fromCharacter?->toArray()
            ],
            'agent' => [
                'user' => $this->agentUser?->toAdminArray(),
                'character' => $this->agentCharacter?->toArray()
            ]
        ];
    }

    public function serializeForAgent(SupportTicketService $service) : array
    {
        $output = [
            'id' => $this->id,
            'url' => route('support.agent.ticket', ['id' => $this->id]),
            'category' => $this->category,
            'title' => $this->title,
            'content' => $this->content,
            'createdAt' => $this->createdAt,
            'createdAtTimespan' => $this->createdAt->diffForHumans(),
            'status' => $this->status,
            'statusAt' => $this->statusAt,
            'statusAtTimespan' => $this->statusAt->diffForHumans(),
            'closedAt' => $this->closedAt,
            'closedAtTimespan' => $this->closedAt?->diffForHumans(),
            'closureReason' => $this->closureReason,
            'isPublic' => $this->isPublic,
            'updatedAt' => $this->updatedAt,
            'updatedAtTimespan' => $this->updatedAt->diffForHumans(),
            'from' => [
                'user' => $this->fromUser->toAdminArray(),
                'character' => $this->fromCharacter?->toArray()
            ],
            'agent' => [
                'user' => $this->agentUser?->toAdminArray(),
                'character' => $this->agentCharacter?->toArray()
            ]
        ];

        $output['log'] = array_map(function($entry) {
            return $entry->toAdminArray();
        }, $service->getLog($this));

        $output['links_from'] = [];
        $output['links_to'] = [];
        foreach ($service->getLinks($this) as $link) {
            if ($link->from->id == $this->id)
                $output['links_to'][] = $link->toAgentArray();
            else
                $output['links_from'][] = $link->toAgentArray();
        }

        $output['watchers'] = [];
        foreach ($service->getWatchers($this) as $user) {
            $output['watchers'][] = $user->toAdminArray();
        }
        return $output;
    }
}
