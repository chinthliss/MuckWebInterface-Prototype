<?php

namespace App\SupportTickets;

use App\User;
use JetBrains\PhpStorm\ArrayShape;

class SupportTicketSubscription
{
    public SupportTicket $ticket;

    public User $user;

    public string $interest;

    public function __construct(SupportTicket $ticket, User $user, string $interest)
    {
        $this->ticket = $ticket;
        $this->user = $user;
        $this->interest = $interest;
    }

    #[ArrayShape(['ticketId' => "int", 'accountId' => "int", 'accountUrl' => "string", 'interest' => "string"])]
    public function toAdminArray() : array
    {
        return [
            'ticketId' => $this->ticket->id,
            'accountId' => $this->user->getAid(),
            'accountUrl' => $this->user->getAdminUrl(),
            'interest' => $this->interest
        ];
    }
}
