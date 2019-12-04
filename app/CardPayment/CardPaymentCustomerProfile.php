<?php


namespace App\CardPayment;

/**
 * Due to a lack of familiarity, this is more to leave room for abstraction.
 */
interface CardPaymentCustomerProfile
{
    /**
     * @param $response
     * @return CardPaymentCustomerProfile
     */
    public static function fromApiResponse ($response);

    public function getMerchantCustomerId();
    public function getCustomerProfileId();

    public function getCard(string $cardId);
    public function setCard(string $cardId, Card $card);
}
