<?php

namespace App\SupportTickets;

use App\Muck\MuckDbref;
use App\User;

interface SupportTicketProvider
{
    /**
     * @param int $id
     * @return ?SupportTicket
     */
    public function getById(int $id): ?SupportTicket;

    /**
     * @param string $category
     * @return array<int, SupportTicket>
     */
    public function getByCategory(string $category): array;

    /**
     * @return array<int, SupportTicket>
     */
    public function getOpen(): array;

    /**
     * @return array<int, SupportTicket>
     */
    public function getActive(): array;

    /**
     * @param string $category
     * @param string $title
     * @param string $content
     * @param User|null $user
     * @param MuckDbref|null $character
     * @return SupportTicket
     */
    public function create(string $category, string $title, string $content,
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
     * @param MuckDbref|null $fromMuckObject
     * @param string $content
     */
    public function log(SupportTicket $ticket, string $logType, bool $isPublic, ?User $fromUser, ?MuckDbref $fromMuckObject, string $content): void;

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
     * Returns an array of aid:interest
     * @param SupportTicket $ticket
     * @return array<int, string>
     */
    public function getSubscriptions(SupportTicket $ticket): array;

    /**
     * @param SupportTicket $ticket
     * @param User $user
     * @param string $interest
     */
    public function addSubscription(SupportTicket $ticket, User $user, string $interest): void;

    /**
     * @param SupportTicket $ticket
     * @param User $user
     */
    public function removeSubscription(SupportTicket $ticket, User $user, string $interest): void;
}
