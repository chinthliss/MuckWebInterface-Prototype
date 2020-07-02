<?php


namespace App\Payment;


/**
 * Basic customer profile
 */
class CardPaymentCustomerProfile
{
    protected $id;

    /**
     * @var Card[] Stored as {paymentProfileId:Card}
     */
    protected $cards = [];

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCustomerProfileId()
    {
        return $this->id;
    }

    /**
     * @return array<int, Card[]>
     */
    public function getCards() : array
    {
        return $this->cards;
    }

    public function addCard(Card $card)
    {
        $this->cards[$card->id] = $card;
    }

    public function removeCard(Card $card)
    {
        unset($this->cards[$card->id]);
    }

    public function getCard(string $cardId) : ?Card
    {
        if (array_key_exists($cardId, $this->cards))
            return $this->cards[$cardId];
        else return null;
    }

    public function getDefaultCard() : ?Card
    {
        foreach($this->cards as $card) {
            if ($card->isDefault) return $card;
        }
        return null;
    }
}
