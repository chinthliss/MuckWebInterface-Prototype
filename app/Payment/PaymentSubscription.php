<?php


namespace App\Payment;

//Holding class for a subscriptions details

use Illuminate\Support\Carbon;

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

    /**
     * @var Carbon|null
     */
    public $createdAt = null;

    /**
     * @var Carbon|null
     */
    public $closedAt = null;

    /**
     * @var Carbon|null
     */
    public $nextChargeAt = null;

    /**
     * @var Carbon|null
     */
    public $lastChargeAt = null;

    /**
     * @var string One of: approval_pending, user_declined, active, suspended, cancelled, expired
     */
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
     * @return Carbon When a subscription will expire unless renewed.
     */
    public function expires(): Carbon
    {
        return $this->lastChargeAt ? $this->lastChargeAt->copy()->addDays($this->recurringInterval + 1)->startOfDay()
            : $this->createdAt;
    }

    /**
     * @return bool Whether a subscription covers 'now' (even if no longer renewing)
     */
    public function active(): bool
    {
        return $this->expires() >= Carbon::now();
    }

    public function renewing(): bool
    {
        return $this->status == 'active';
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
            "last_charge_at" => $this->lastChargeAt,
            "closed_at" => $this->closedAt,
            "link" => route('accountcurrency.subscription', ['id' => $this->id])
        ];
    }

}
