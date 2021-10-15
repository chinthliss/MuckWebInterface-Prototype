<?php

namespace App\SupportTickets;

class SupportTicketLink
{
    public SupportTicket $from;
    public SupportTicket $to;
    public string $type;

    public function __construct(
        SupportTicket $from,
        SupportTicket $to,
        string        $type
    )
    {
        $this->from = $from;
        $this->to = $to;
        $this->type = $type;
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->id,
            'to' => $this->to->id,
            'type' => $this->type,
        ];
    }
}
