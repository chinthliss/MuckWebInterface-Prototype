<?php


namespace App\Payment;

//Holding class for a subscriptions details

class PaymentSubscription
{
    /**
     * @var string|null
     */
    public $id = null;

    /**
     * @var int|null
     */
    public $accountId = null;

    /**
     * Actual vendor used.
     * @var string|null
     */
    public $vendor = null;

    /**
     * @var string|null
     */
    public $vendorProfileId = null;

    /**
     * @var string|null
     */
    public $vendorSubscriptionId = null;

    /**
     * @var string|null
     */
    public $vendorSubscriptionPlanId = null;

    /**
     * @var float The USD value associated with the subscription
     */
    public $amountUsd = null;

    /**
     * @var int Frequency in days between payments
     */
    public $recurringInterval = null;

    public $createdAt = null;

    public $closedAt = null;

    public $nextChargeAt = null;

    public $status = 'unknown';

    /**
     * Non-vendor-specific payment type, such as Card or Paypal
     * @var string|null
     * @return string
     */
    public function type(): string
    {
        if ($this->vendor === 'paypal') return 'Paypal';
        if ($this->vendor === 'authorizenet') return 'Card';
        return 'Unknown';

    }

    /**
     * @return bool Whether stuff can be done to this subscription
     */
    public function open(): bool
    {
        return ($this->closedAt ? false : true);
    }

    /**
     * Produces the array used to offer a user the chance to accept/decline the transaction
     * @return array
     */
    public function toSubscriptionOfferArray(): array
    {
        return [
            "token" => $this->id,
            "purchase" => $this->recurringInterval . " day subscription.",
            "price" => "$" . round($this->amountUsd, 2),
            "note" => "$" . round($this->amountUsd, 2)
                . ' will be recharged every ' . $this->recurringInterval . ' days.'
        ];
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "type" => $this->type(),
            "amount_usd" => $this->amountUsd,
            "recurring_interval" => $this->recurringInterval,
            "status" => $this->status,
            "created_at" => $this->createdAt,
            "next_charge_at" => $this->nextChargeAt,
            "closed_at" => $this->closedAt
        ];
    }

}
