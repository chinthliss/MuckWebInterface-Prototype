<?php


namespace App\Payment;


/**
 * Basic customer profile
 */
class CardPaymentCustomerProfile
{
    protected $id;

    /**
     * @var array<int, Card[]> Stored as {paymentProfileId:Card}
     */
    protected $cards = [];

    /**
     * @var Card|null Reference to default card
     */
    protected $defaultCardId = null;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCustomerProfileId()
    {
        return $this->id;
    }

    public function getCards()
    {
        return $this->cards;
    }

    public function setCard(Card $card)
    {
        $this->cards[$card->id] = $card;
    }

    public function getCard(string $cardId)
    {
        if (array_key_exists($cardId, $this->cards))
            return $this->cards[$cardId];
        else return null;
    }

    public function getDefaultCard()
    {
        if ($this->defaultCardId)
            return $this->getCard($this->defaultCardId);
        return null;
    }
}
