<?php


namespace App\Admin;


use Illuminate\Support\Carbon;

class AccountNote
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $accountId;

    /**
     * @var Carbon
     */
    public $whenAt;

    /**
     * @var string
     */
    public $body;

    /**
     * @var string
     */
    public $staffMember;

    /**
     * @var string
     */
    public $game;
}
