<?php


namespace App\Payment;

use Illuminate\Support\Carbon;
use net\authorize\api\contract\v1\CreateCustomerProfileResponse;
use net\authorize\api\contract\v1\GetCustomerProfileResponse;
use net\authorize\api\contract\v1\SubscriptionPaymentType;

/**
 * Class CardPaymentProfile
 * Utility class for the Authorize.Net Payment manager, represents a customer profile.
 * @package App
 */
class AuthorizeNetCardPaymentCustomerProfile extends CardPaymentCustomerProfile
{
    protected $merchantCustomerId = null;

    /**
     * These are initially retrieved as just the id, so will be in the form id:null until a further call is made
     * @var array<int, SubscriptionPaymentType|null> Stored as {id:subscription}
     */
    protected $subscriptionProfiles = [];

    /**
     * @param $response
     * @return AuthorizeNetCardPaymentCustomerProfile
     */
    public static function fromApiResponse($response): AuthorizeNetCardPaymentCustomerProfile
    {
        $customerProfile = null;

        if ($response instanceof CreateCustomerProfileResponse) {
            $customerProfile = new self($response->getCustomerProfileId());
        } elseif ($response instanceof GetCustomerProfileResponse) {
            $customerProfile = new self($response->getProfile()->getCustomerProfileId());
        } else
            throw new \Exception("fromApiResponse called with unsupported response type.");

        if ($response instanceof GetCustomerProfileResponse) {
            $receivedProfile = $response->getProfile();
            $customerProfile->merchantCustomerId = $receivedProfile->getMerchantCustomerId();

            if ($subscriptionIds = $response->getSubscriptionIds()) {
                foreach ($subscriptionIds as $subscriptionId) {
                    $customerProfile->subscriptionProfiles[$subscriptionId] = null;
                }
            }

            if ($paymentProfiles = $receivedProfile->getPaymentProfiles()) {
                foreach ($paymentProfiles as $paymentProfile) {
                    $receivedCard = $paymentProfile->getPayment()->getCreditCard();
                    $card = new Card();
                    $card->id = $paymentProfile->getCustomerPaymentProfileId();
                    $card->cardType = $receivedCard->getCardType();
                    $card->cardNumber = $receivedCard->getCardNumber();
                    // Returned from API as YYYY-MM
                    $parts = explode('-', $receivedCard->getExpirationDate());
                    $card->expiryDate = Carbon::createFromDate($parts[0], $parts[1], 1);
                    $card->isDefault = $paymentProfile->getDefaultPaymentProfile();
                    $customerProfile->setCard($card);
                    if ($paymentProfile->getDefaultPaymentProfile()) $customerProfile->defaultCardId = $card->id;
                }
            }
        }
        return $customerProfile;

    }

    public function getMerchantCustomerId()
    {
        return $this->merchantCustomerId;
    }

}
