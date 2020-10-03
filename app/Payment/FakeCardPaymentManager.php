<?php


namespace App\Payment;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $profile = null;

        //Try to actually load one
        $row = DB::table('billing_profiles')->where([
            'aid' => $accountId
        ])->first();

        if ($row->profileid) {
            $profile = new CardPaymentCustomerProfile($row->profileid);
            //Try to load cards
            $cardRows = DB::table('billing_paymentprofiles')->where([
                'profileid' => $profile->getCustomerProfileId()
            ])->get();
            foreach ($cardRows as $row) {
                $card = new Card();
                $card->id = $row->id;
                $card->cardType = $row->cardtype;
                $card->cardNumber = $row->maskedcardnum;
                $card->expiryDate = $row->expdate;
                $profile->addCard($card);
            }
        }
        else $profile = new CardPaymentCustomerProfile(count($this->customerProfiles));

        $this->customerProfiles[$accountId] = $profile;
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
        $card->cardType = 'Fake';
        $profile->addCard($card);
        $this->setDefaultCardFor($user, $card);
        return $card;
    }

    /**
     * @inheritDoc
     */
    public function deleteCardFor(User $user, Card $card): void
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $profile->removeCard($card);
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
    public function chargeCardFor(User $user, Card $card, PaymentTransaction $transaction): string
    {
        return 'NO';
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
