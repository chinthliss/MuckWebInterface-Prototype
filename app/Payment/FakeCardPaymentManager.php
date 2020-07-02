<?php


namespace App\Payment;

use App\User;
use Illuminate\Support\Carbon;

class FakeCardPaymentManager implements CardPaymentManager
{

    /**
     * @var array<int, CardPaymentCustomerProfile>
     */
    private $customerProfiles = [];

    private function loadOrCreateProfileFor(User $user)
    {
        $accountId = $user->getAid();
        //Return if already fetched
        if (array_key_exists($accountId, $this->customerProfiles)) return $this->customerProfiles[$accountId];

        /** @var CardPaymentCustomerProfile $profile */
        $profile = new CardPaymentCustomerProfile(count($this->customerProfiles));

        $this->customerProfiles[$profile->getCustomerProfileId()] = $profile;
        return $profile;
    }

     /**
     * @inheritDoc
     */
    public function createCardFor(User $user, string $cardNumber, string $expiryDate, string $securityCode): Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $card = new Card();
        $card->id = count($profile->getCards());
        $card->cardNumber = $cardNumber;
        // $expiryDate is in the form MM/YYYY
        $parts = explode('/', $expiryDate);
        $card->expiryDate = Carbon::createFromDate($parts[1], $parts[0], 1);

        $card->expiryDate = $expiryDate;
        $card->cardType = 'Fake';
        $profile->setCard($card);
        return $card;
    }

    /**
     * @inheritDoc
     */
    public function deleteCardFor(User $user, Card $card): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function setDefaultCardFor(User $user, Card $defaultCard): void
    {
        foreach ($this->getCardsFor($user) as $card) {
            $card->isDefault = false;
        }
        $defaultCard->isDefault = true;
    }

    /**
     * @inheritDoc
     */
    public function chargeCardFor(User $user, Card $card, float $amountToChargeUsd): string
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultCardFor(User $user): ?Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile ? $profile->getDefaultCard()  : null;
    }

    /**
     * @inheritDoc
     */
    public function getCardFor(User $user, int $cardId): ?Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile ? $profile->getCard($cardId) : null;
    }

    /**
     * @inheritDoc
     */
    public function getCardsFor(User $user): array
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile ? $profile->getCards() : [];
    }

    /**
     * @inheritDoc
     */
    public function getCustomerIdFor(User $user)
    {
        $profile = $this->loadOrCreateProfileFor($user);
        return $profile ? $profile->getCustomerProfileId() : null;
    }
}
