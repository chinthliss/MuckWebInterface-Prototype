<?php


namespace App\CardPayment;

class Card
{
    public $id;
    public $cardType;
    public $cardNumber;
    public $expiryDate;
    public $isDefault;

    /*
     * @var int[]
     */
    public $subscriptions = [];
}
