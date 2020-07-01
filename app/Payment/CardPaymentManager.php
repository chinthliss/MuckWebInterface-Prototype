<?php


namespace App\Payment;

use App\User;

interface CardPaymentManager
{
    /**
     * @param User $user
     * @param string $cardNumber
     * @param string $expiryDate in the form MM/YYYY
     * @param string $securityCode
     * @return Card
     */
    public function createCardFor(User $user, string $cardNumber, string $expiryDate, string $securityCode): Card;

    /**
     * @param User $user
     * @param Card $card
     */
    public function deleteCardFor(User $user, Card $card): void;

    /**
     * @param User $user
     * @param Card $card
     */
    public function setDefaultCardFor(User $user, Card $card): void;

    /**
     * @param User $user
     * @param Card $card
     * @param float $amountToChargeUsd
     * @return string Reference
     */
    public function chargeCardFor(User $user, Card $card, float $amountToChargeUsd): string;

    public function getDefaultCardFor(User $user): ?Card;

    public function getCardFor(User $user, int $cardId): ?Card;

    public function getCardsFor(User $user): array;

    public function getCustomerIdFor(User $user);

}
