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

    public function maskedCardNumber()
    {
        return '..' . substr($this->cardNumber, -4);
    }

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'cardType' => $this->cardType,
            'maskedCardNumber' => $this->maskedCardNumber(),
            'expiryDate' => $this->expiryDate,
            'isDefault' => $this->isDefault
        );
    }
}
