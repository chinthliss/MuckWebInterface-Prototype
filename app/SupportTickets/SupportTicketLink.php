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
            'from_title' => $this->from->title,
            'from_url' => route('support.user.ticket', ['id' => $this->from->id]),
            'to' => $this->to->id,
            'to_title' => $this->to->title,
            'to_url' => route('support.user.ticket', ['id' => $this->to->id]),
            'type' => ucfirst($this->type),
        ];
    }

    public function toAgentArray(): array
    {
        return [
            'from' => $this->from->id,
            'from_title' => $this->from->title,
            'from_url' => route('support.agent.ticket', ['id' => $this->from->id]),
            'to' => $this->to->id,
            'to_title' => $this->to->title,
            'to_url' => route('support.agent.ticket', ['id' => $this->to->id]),
            'type' => ucfirst($this->type),
        ];
    }

}
