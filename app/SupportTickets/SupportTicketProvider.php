<?php

namespace App\SupportTickets;

use App\Muck\MuckDbref;
use App\User;
use Illuminate\Support\Carbon;

interface SupportTicketProvider
{
    /**
     * @param int $id
     * @return ?SupportTicket
     */
    public function getById(int $id): ?SupportTicket;

    /**
     * @param string $categoryCode
     * @return SupportTicket[]
     */
    public function getByCategory(string $categoryCode): array;

    /**
     * @return SupportTicket[]
     */
    public function getFrom(User $user): array;

    /**
     * @return SupportTicket[]
     */
    public function getOpen(): array;

    /**
     * Active tickets are those that are open or have been updated in any way in the last 3 days.
     * @return SupportTicket[]
     */
    public function getActive(): array;

    /**
     * Gets just the updated at time of a ticket, used for polling to check for changes
     * @return Carbon
     */
    public function getUpdatedAt(int $id): Carbon;

    /**
     * @param string $categoryCode
     * @param string $title
     * @param string $content
     * @param User|null $user
     * @param MuckDbref|null $character
     * @return SupportTicket
     */
    public function create(string $categoryCode, string $title, string $content, ?int $gameCode,
                           ?User  $user, ?MuckDbref $character): SupportTicket;

    /**
     * @param SupportTicket $ticket
     */
    public function save(SupportTicket $ticket);

    /**
     * @param SupportTicket $ticket
     * @param string $logType
     * @param bool $isPublic
     * @param User|null $fromUser
     * @param MuckDbref|null $fromMuckDbref
     * @param string $content
     */
    public function log(SupportTicket $ticket, string $logType, bool $isPublic, ?User $fromUser, ?MuckDbref $fromMuckDbref, string $content): void;

    /**
     * @param SupportTicket $ticket
     * @return SupportTicketLog[]
     */
    public function getLog(SupportTicket $ticket): array;

    /**
     * @param SupportTicket $from
     * @param SupportTicket $to
     * @param string $linkType
     */
    public function link(SupportTicket $from, SupportTicket $to, string $linkType): void;

    /**
     * @param SupportTicket $ticket
     * @return SupportTicketLink[]
     */
    public function getLinks(SupportTicket $ticket): array;

    /**
     * Returns an array of Users watching this ticket
     * @param SupportTicket $ticket
     * @return User[]
     */
    public function getWatchers(SupportTicket $ticket): array;

    /**
     * @param SupportTicket $ticket
     * @param User $user
     */
    public function addWatcher(SupportTicket $ticket, User $user): void;

    /**
     * @param SupportTicket $ticket
     * @param User $user
     */
    public function removeWatcher(SupportTicket $ticket, User $user): void;
}
