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

    private array $validStatuses = ['new', 'open', 'pending', 'held', 'closed'];

    private array $validClosureReasons = ['completed', 'denied', 'duplicate'];

    private array $validLogTypes = ['system', 'note', 'upvote', 'downvote'];

    private array $validLinkTypes = ['duplicate', 'related'];

    /**
     * @var SupportTicketCategory[]
     */
    private array $categoryConfiguration;

    /**
     * @return SupportTicketCategory[]
     */
    public function getCategoryConfiguration(): array
    {
        if (!isset($this->categoryConfiguration)) {
            // For now hard coded. May replace this with something more dynamic later
            $this->categoryConfiguration = [
                new SupportTicketCategory('account', 'Account', neverPublic: true),
                new SupportTicketCategory('chargen', 'Chargen'),
                new SupportTicketCategory('building', 'Building'),
                new SupportTicketCategory('code', 'Code'),
                new SupportTicketCategory('disputes', 'Disputes', neverPublic: true, requiresCharacter: true),
                new SupportTicketCategory('gear', 'Gear', requiresCharacter: true),
                new SupportTicketCategory('judge', 'Judge'),
                new SupportTicketCategory('typo', 'Typo'),
                new SupportTicketCategory('makopool', 'Mako Pool', usersCannotRaise: true),
                new SupportTicketCategory('monsterreview', 'Monster Review', usersCannotRaise: true),
                new SupportTicketCategory('research', 'Research', usersCannotRaise: true),
                new SupportTicketCategory('stretchgoal', 'Stretch Goal', usersCannotRaise: true),
                new SupportTicketCategory('suggestion', 'Suggestion')
            ];
        }
        return $this->categoryConfiguration;
    }

    public function __construct(SupportTicketProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns whether the given user is allowed to view this ticket
     * @param User $user
     * @param SupportTicket $ticket
     * @return bool
     */
    public function userCanSeeTicket(User $user, SupportTicket $ticket): bool
    {
        return $ticket->fromUser->is($user) || $ticket->isPublic;
    }

    /**
     * @param int $id
     * @return SupportTicket|null
     */
    public function getTicketById(int $id): ?SupportTicket
    {
        return $this->provider->getById($id);
    }

    // Streamlined method for polling the updated by
    public function getLastUpdatedById(int $id): Carbon
    {
        return $this->provider->getUpdatedAt($id);
    }

    /**
     * @return array<int, SupportTicket>
     */
    public function getOpenTickets(): array
    {
        return $this->provider->getOpen();
    }

    /**
     * Gets all active tickets
     * @return array<int, SupportTicket>
     */
    public function getActiveTickets(): array
    {
        return $this->provider->getActive();
    }

    /**
     * Gets all active tickets that a particular user can see
     * @return array<int, SupportTicket>
     */
    public function getActiveTicketsForUser(User $user): array
    {
        $results = [];
        foreach ($this->provider->getActive() as $ticket) {
            if ($this->userCanSeeTicket($user, $ticket)) $results[] = $ticket;
        }
        return $results;
    }

    /**
     * @param SupportTicket $ticket
     */
    private function saveTicket(SupportTicket $ticket, bool $amendUpdatedAt = true)
    {
        if ($amendUpdatedAt) $ticket->updatedAt = Carbon::now();
        $this->provider->save($ticket);
    }

    /**
     * Internal function to avoid duplication.
     * @param SupportTicket $ticket
     */
    private function potentiallyUpdateStatusAutomatically(SupportTicket $ticket)
    {
        // New tickets change to open if something is done on them
        if ($ticket->status == 'new') {
            $this->setStatus($ticket, 'open');
        }
    }

    /**
     * @param string $category
     * @param string $title
     * @param string $content
     * @param User|null $user
     * @param MuckCharacter|null $character
     * @return SupportTicket
     */
    public function createTicket(string $category, string $title, string $content,
                                 ?User  $user = null, ?MuckCharacter $character = null): SupportTicket
    {
        if ($user && $character && $user->getAid() !== $character->aid())
            throw new Error("Attempt to create a ticket with a character and user with different accountIDs");
        return $this->provider->create($category, $title, $content, $user, $character);
    }

    /**
     * @param SupportTicket $ticket
     * @param string $closureReason
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     * @throws Exception
     */
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
        $this->addLogEntry($ticket, 'system', true, $fromUser, $fromCharacter, "Ticket closed with reason: " . ucfirst($closureReason));
    }

    /**
     * @param SupportTicket $ticket
     * @param string $status
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     */
    public function setStatus(SupportTicket $ticket, string $status, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        if (!in_array($status, $this->validStatuses))
            throw new Error("Invalid status specified when setting ticket status");

        if ($status === 'closed')
            throw new Error("Closing a ticket should be done by the closeTicket function");

        $ticket->status = $status;
        $ticket->statusAt = Carbon::now();
        if ($ticket->closedAt) {
            $ticket->closedAt = null;
            $ticket->closureReason = null;
            $message = "Ticket re-opened and status changed to: " . ucfirst($status);
        } else {
            $message = "Status changed to: " . ucfirst($status);
        }

        $this->saveTicket($ticket);
        $this->addLogEntry($ticket, 'system', true, $fromUser, $fromCharacter, $message);
    }

    /**
     * @param SupportTicket $ticket
     * @param bool $isPublic
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     * @throws Exception
     */
    public function setPublic(SupportTicket $ticket, bool $isPublic, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        if ($ticket->isPublic === $isPublic) return;

        if ($ticket->isPublic) {
            // Need to remove all watchers
            $watchers = $this->provider->getWatchers($ticket);
            foreach ($watchers as $watcher) {
                $this->removeWatcher($ticket, $watcher);
            }
            $message = "Ticket has been made private.";
        } else {
            $message = "Ticket has been made public.";
        }

        $ticket->isPublic = $isPublic;
        $this->addLogEntry($ticket, 'system', true, $fromUser, $fromCharacter, $message);
        $this->saveTicket($ticket);
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
     * @param bool $isPublic
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     */
    public function addNote(SupportTicket $ticket, string $note, bool $isPublic, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        $this->addLogEntry($ticket, 'note', $isPublic, $fromUser, $fromCharacter, $note);

        // If a ticket is pending and the requester adds a response, it changes back to open
        if ($ticket->status == 'pending' && $fromUser->is($ticket->fromUser)) {
            $this->setStatus($ticket, 'open');
        }

        $this->potentiallyUpdateStatusAutomatically($ticket);

        // And finally save ticket to update the updatedAt time.
        $this->saveTicket($ticket);
    }

    /**
     * @param SupportTicket $from
     * @param SupportTicket $to
     * @param string $linkType
     * @param User|null $fromUser
     * @param MuckDbref|null $fromCharacter
     * @throws Exception
     */
    public function linkTickets(SupportTicket $from, SupportTicket $to, string $linkType, ?User $fromUser = null, ?MuckDbref $fromCharacter = null)
    {
        if (!in_array($linkType, $this->validLinkTypes))
            throw new Error("Invalid link type specified when linking tickets");

        if ($from->id === $to->id)
            throw new Error("Attempt to create a link from and to the same ticket!");

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
     * @return User[]
     */
    public function getWatchers(SupportTicket $ticket): array
    {
        return $this->provider->getWatchers($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param User $user
     * @throws Exception
     */
    public function addWatcher(SupportTicket $ticket, User $user)
    {
        foreach ($this->provider->getWatchers($ticket) as $existing) {
            if ($existing->is($user))
                throw new Exception('Already watching that ticket');
        }

        $this->provider->addWatcher($ticket, $user);

        $this->addLogEntry($ticket, 'system', false, $user, null, "User started watching ticket");
        $this->saveTicket($ticket, false);
    }

    /**
     * @param SupportTicket $ticket
     * @param User $user
     * @throws Exception
     */
    public function removeWatcher(SupportTicket $ticket, User $user)
    {
        $found = false;
        foreach ($this->provider->getWatchers($ticket) as $watcher) {
            if ($watcher->is($user)) $found = true;
        }
        if (!$found) throw new Exception("Nothing to remove - User isn't watching the ticket.");

        $this->provider->removeWatcher($ticket, $user);

        $this->addLogEntry($ticket, 'system', false, $user, null, "User stopped watching ticket");
        $this->saveTicket($ticket, false);
    }

    /**
     * @param SupportTicket $ticket
     * @param string $title
     * @param User|null $user
     * @param MuckCharacter|null $character
     */
    public function setTitle(SupportTicket $ticket, string $title,
                             ?User         $user = null, ?MuckCharacter $character = null)
    {
        $ticket->title = $title;
        $this->addLogEntry($ticket, 'system', true, $user, $character,
            "Title changed to: $title");
        $this->saveTicket($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param string $category
     * @param User|null $user
     * @param MuckCharacter|null $character
     */
    public function setCategory(SupportTicket $ticket, string $category,
                                ?User         $user = null, ?MuckCharacter $character = null)
    {
        $found = null;
        foreach ($this->getCategoryConfiguration() as $possibleCategory) {
            if ($possibleCategory->code === $category) $found = $possibleCategory;
        }
        if (!$found) throw new Error("Specified category ($category) is not valid.");

        $ticket->category = $category;
        $this->addLogEntry($ticket, 'system', true, $user, $character,
            "Category changed to: $category");
        $this->saveTicket($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param User|null $user
     * @param MuckCharacter|null $character
     * @throws Exception
     */
    public function setAgent(SupportTicket $ticket, ?User $user, ?MuckCharacter $character = null)
    {
        if ($ticket->closedAt) throw new Exception("Ticket is closed.");

        $ticket->agentUser = $user;
        $ticket->agentCharacter = $character;

        if ($user) {
            if ($character)
                $message = "Ticket assigned to: " . $character->name();
            else
                $message = "Ticked assigned";
        } else $message = "Ticket unassigned";

        $this->addLogEntry($ticket, 'system', true, $user, $character, $message);

        $this->potentiallyUpdateStatusAutomatically($ticket);

        $this->saveTicket($ticket);
    }

    /**
     * @param SupportTicket $ticket
     * @param User $user
     * @param MuckCharacter|null $character
     * @return bool
     */
    public function hasVoted(SupportTicket $ticket, User $user, ?MuckCharacter $character = null) : bool
    {
        foreach ($this->getLog($ticket) as $logEntry) {
            if ($logEntry->type !== 'upvote' && $logEntry->type !== 'downvote') continue;
            if ($logEntry->user->is($user) && $logEntry->character === $character) return true;
        }
        return false;
    }

    /**
     * @param SupportTicket $ticket
     * @param string $voteType 'up' or 'down'
     * @param User $user
     * @param MuckCharacter|null $character
     * @throws Exception
     */
    public function voteOn(SupportTicket $ticket, string $voteType, User $user, ?MuckCharacter $character = null)
    {
        if ($ticket->closedAt) throw new Exception("Ticket is closed.");

        if ($this->hasVoted($ticket, $user, $character)) throw new Exception("Already voted on that ticket.");
        switch ($voteType) {
            case 'up':
                $ticket->votesUp++;
                $logType = 'upvote';
                $message = "Ticket voted for: ";
                break;
            case 'down':
                $ticket->votesDown++;
                $logType = 'downvote';
                $message = "Ticket voted against: ";
                break;
            default:
                throw new Exception ("Invalid vote type.");
        }
        if ($character)
            $message .= $character->name();
        else
            $message .= "Account#" . $user->getAid();

        $this->addLogEntry($ticket, $logType, false, $user, $character, $message);

        $this->saveTicket($ticket, false);
    }

}
