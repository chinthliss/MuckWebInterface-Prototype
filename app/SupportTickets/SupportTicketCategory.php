<?php

namespace App\SupportTickets;

// Presently just a utility class
class SupportTicketCategory
{
    public string $code;

    public string $name;

    public bool $usersCannotRaise = false;

    public bool $neverPublic = false;

    public function __construct(string $code, string $name, $usersCannotRaise = false, $neverPublic = false)
    {
        $this->code = $code;
        $this->name = $name;
        $this->usersCannotRaise = $usersCannotRaise;
        $this->neverPublic = $neverPublic;
    }
}
