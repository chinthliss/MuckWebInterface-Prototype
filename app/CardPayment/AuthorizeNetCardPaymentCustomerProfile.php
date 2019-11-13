<?php


namespace App\CardPayment;

use net\authorize\api\contract\v1\CustomerPaymentProfileMaskedType;
use net\authorize\api\contract\v1\GetCustomerProfileResponse;
use net\authorize\api\contract\v1\SubscriptionPaymentType;

/**
 * Class CardPaymentProfile
 * Represents an Authorize.Net customer profile.
 * @package App
 */
class AuthorizeNetCardPaymentCustomerProfile implements CardPaymentCustomerProfile
{
    protected $id;
    protected $merchantCustomerId = null;

    /**
     * @var array<int, Card[]> Stored as {paymentProfileId:Card}
     */
    protected $cards = [];

    protected $defaultCardId = null;

    /**
     * These are initially retrieved as just the id, so will be in the form id:null until a further call is made
     * @var array<int, SubscriptionPaymentType|null> Stored as {id:subscription}
     */
    protected $subscriptionProfiles = [];

    public function __construct($id)
    {
        $this->id = $id;
    }


    /**
     * @param GetCustomerProfileResponse $response
     * @return AuthorizeNetCardPaymentCustomerProfile
     */
    public static function fromApiResponse($response)
    {
        $receivedProfile = $response->getProfile();
        $customerProfile = new self($receivedProfile->getCustomerProfileId());

        $customerProfile->merchantCustomerId = $receivedProfile->getMerchantCustomerId();

        foreach ($response->getSubscriptionIds() as $subscriptionId) {
            $customerProfile->subscriptionProfiles[$subscriptionId] = null;
        }

        foreach ($receivedProfile->getPaymentProfiles() as $paymentProfile) {
            $receivedCard = $paymentProfile->getPayment()->getCreditCard();
            $card = new Card();
            $card->id = $paymentProfile->getCustomerPaymentProfileId();
            $card->cardType = $receivedCard->getCardType();
            $card->cardNumber = $receivedCard->getCardNumber();
            $card->expirationDate = $receivedCard->getExpirationDate();
            $customerProfile->setCard($card->id, $card);
            if ($paymentProfile->getDefaultPaymentProfile()) $customerProfile->defaultCardId = $card->id;
        }

        return $customerProfile;
    }

    public function getMerchantCustomerId()
    {
        return $this->merchantCustomerId;
    }

    public function setCard(string $cardId, Card $card)
    {
        $this->cards[$cardId] = $card;
    }

    public function getCard(string $cardId)
    {
        return $this->cards[$cardId];
    }

}
