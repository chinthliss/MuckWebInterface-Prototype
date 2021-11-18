<?php

namespace App\SupportTickets;

use Error;

// Presently just a utility class, may extend later
class SupportTicketCategory
{
    private static array $validTypes = [
        'issue',   // Something broken
        'request', // Request to do something
        'task'     // Automated task
    ];

    public string $code;

    public string $name;

    public string $type;

    public string $description;

    public bool $usersCannotRaise = false;

    public bool $neverPublic = false;

    public bool $notGameSpecific = false;

    public function __construct(string $code, string $name, string $type, string $description,
                                       $usersCannotRaise = false, $neverPublic = false, $notGameSpecific = false)
    {
        if (!in_array($type, $this::$validTypes))
            throw new Error("Invalid category type specified");
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->usersCannotRaise = $usersCannotRaise;
        $this->neverPublic = $neverPublic;
        $this->notGameSpecific = $notGameSpecific;
    }
}
