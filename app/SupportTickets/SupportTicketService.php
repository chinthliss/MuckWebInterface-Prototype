<?php

namespace App\SupportTickets;

use App\Muck\MuckCharacter;
use App\Muck\MuckDbref;
use App\User;
use Error;
use Exception;
use Illuminate\Support\Carbon;

class SupportTicketService
{
    private SupportTicketProvider $provider;

    private array $validClosureReasons = ['completed', 'denied', 'duplicate'];

    private array $validLogTypes = ['system', 'note', 'upvote', 'downvote'];

    private array $validLinkTypes = ['duplicate', 'related'];

    private array $validInterestTypes = ['watch', 'work'];

    public function __construct(SupportTicketProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getTicketById(int $id): ?SupportTicket
    {
        return $this->provider->getById($id);
    }

    /**
     * @return array<int, SupportTicket>
     */
    public function getOpenTickets(): array
    {
        return $this->provider->getOpen();
    }

    /**
     * @return array<int, SupportTicket>
     */
    public function getActiveTickets(): array
    {
        return $this->provider->getActive();
    }

    private function saveTicket(SupportTicket $ticket)
    {
        $ticket->updatedAt = Carbon::now();
        $this->provider->save($ticket);
    }

    public function createTicket(string $category, string $title, string $content,
                                 ?User  $user = null, ?MuckCharacter $character = null): SupportTicket
    {
        if ($user && $character && $user->getAid() !== $character->aid())
            throw new Error("Attempt to create a ticket with a character and user with different accountIDs");
        return $this->provider->create($category, $title, $content, $user, $character);
    }

    public function closeTicket(SupportTicket $ticket, string $closureReason, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        if (!in_array($closureReason, $this->validClosureReasons))
            throw new Error("Invalid closure reason specified when closing ticket");

        if ($ticket->closedAt) {
            throw new Exception("Ticket already closed");
        }

        $ticket->status = 'closed';
        $ticket->statusAt = Carbon::now();
        $ticket->closureReason = $closureReason;
        $ticket->closedAt = Carbon::now();
        $this->saveTicket($ticket);
        $this->addLogEntry($ticket, 'system', true, $fromUser, $fromCharacter, "Ticket closed with reason: $closureReason");
    }

    /**
     * Internal function to add a log entry.
     * Assumes the calling function will update the ticket's updatedAt value somehow.
     * @param SupportTicket $ticket
     * @param string $logType
     * @param bool $isPublic
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     * @param string $content
     */
    private function addLogEntry(SupportTicket $ticket, string $logType, bool $isPublic, ?User $fromUser, ?MuckDbref $fromCharacter, string $content)
    {
        if (!in_array($logType, $this->validLogTypes))
            throw new Error("Invalid log type specified when adding log entry.");
        $this->provider->log($ticket, $logType, $isPublic, $fromUser, $fromCharacter, $content);
    }

    /**
     * @param SupportTicket $ticket
     * @return SupportTicketLog[]
     */
    public function getLog(SupportTicket $ticket): array
    {
        return $this->provider->getLog($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param string $note
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     */
    public function addNote(SupportTicket $ticket, string $note, bool $isPublic, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        $this->addLogEntry($ticket, 'note', $isPublic, $fromUser, $fromCharacter, $note);
    }

    public function linkTickets(SupportTicket $from, SupportTicket $to, string $linkType, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        if (!in_array($linkType, $this->validLinkTypes))
            throw new Error("Invalid link type specified when linking tickets");

        $existingLinks = $this->provider->getLinks($from);
        foreach ($existingLinks as $link) {
            if ($link->from->id == $from->id and $link->to->id == $to->id) throw new Exception("Link already exists.");
        }
        $this->provider->link($from, $to, $linkType);
        $this->addLogEntry($from, 'system', true, $fromUser, $fromCharacter, "Ticket linked to Ticket#$to->id as $linkType");
        $this->addLogEntry($to, 'system', true, $fromUser, $fromCharacter, "Ticket linked from Ticket#$from->id as $linkType");

        // Save tickets to update the updated times
        $this->saveTicket($from);
        $this->saveTicket($to);
    }

    /**
     * @param SupportTicket $ticket
     * @return SupportTicketLink[]
     */
    public function getLinks(SupportTicket $ticket): array
    {
        return $this->provider->getLinks($ticket);
    }

    /**
     * Returns an array of aid:interest
     * @param SupportTicket $ticket
     * @return array<int, string>
     */
    public function getSubscriptions(SupportTicket $ticket): array
    {
        return $this->provider->getSubscriptions($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $interest
     */
    public function addSubscription(SupportTicket $ticket, User $user, string $interest)
    {
        if (!in_array($interest, $this->validInterestTypes))
            throw new Error("Invalid interest type specified when adding subscription");

        foreach ($this->provider->getSubscriptions($ticket) as $existingAid => $existingInterest) {
            if ($existingAid == $user->getAid() && $existingInterest == $interest)
                throw new Exception('Subscription already exists');
        }

        $this->provider->addSubscription($ticket, $user, $interest);

        $message = match ($interest) {
            'work' => "Agent started working on ticket: $user.",
            'watch' => "User started watching ticket: $user.",
            default => null,
        };
        $this->addLogEntry($ticket, 'system', false, $user, null, $message);

        $this->saveTicket($ticket);

    }

    public function removeSubscription(SupportTicket $ticket, User $user, string $interest)
    {
        if (!in_array($interest, $this->validInterestTypes))
            throw new Error("Invalid interest type specified when removing subscription");

        $found = false;
        foreach ($this->provider->getSubscriptions($ticket) as $existingAid => $existingInterest) {
            if ($existingAid == $user->getAid() && $existingInterest == $interest)
                $found = true;
        }
        if (!$found) throw new Exception("Subscription doesn't exist.");

        $this->provider->removeSubscription($ticket, $user, $interest);

        $message = match ($interest) {
            'work' => "Agent stopped working on ticket: $user.",
            'watch' => "User stopped watching ticket: $user.",
            default => null,
        };
        $this->addLogEntry($ticket, 'system', false, $user, null, $message);

        $this->saveTicket($ticket);

    }

}
