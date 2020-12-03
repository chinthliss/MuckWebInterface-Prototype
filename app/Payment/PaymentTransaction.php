<?php


namespace App\Payment;

//Holding class for a transactions details

class PaymentTransaction
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
    public $vendorTransactionId = null;

    /**
     * @var string|null
     */
    public $subscriptionId = null;

    /**
     * @var string
     */
    public $purchaseDescription = "";

    /**
     * @var float The USD value associated with only an account currency purchase
     */
    public $accountCurrencyPriceUsd = 0.0;

    /**
     * @var float The USD value associated with item purchases
     */
    public $itemPriceUsd = 0.0;

    /**
     * @var int
     */
    public $accountCurrencyQuoted = 0;

    /**
     * @var int Account currency rewarded from direct purchase - can vary due to bonuses
     */
    public $accountCurrencyRewarded = 0;

    /**
     * @var int Any account currency that was rewarded for item purchases.
     */
    public $accountCurrencyRewardedForItems = 0;

    /**
     * @var PaymentTransactionItem[]
     */
    public $items = [];

    public $createdAt = null;

    public $paidAt = null;

    public $completedAt = null;

    public $result = 'unknown';

    public function totalPriceUsd(): float
    {
        return $this->accountCurrencyPriceUsd + $this->itemPriceUsd;
    }

    public function totalAccountCurrencyRewarded(): int
    {
        return $this->accountCurrencyRewarded + $this->accountCurrencyRewardedForItems;
    }


    /**
     * Whether a transaction can be acted upon
     * @return bool
     */
    public function open(): bool
    {
        return ($this->completedAt ? false : true);
    }

    /**
     * Whether a transaction has been paid
     * @return bool
     */
    public function paid(): bool
    {
        return ($this->paidAt ? true : false);
    }

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
     * Produces the array used to offer a user the chance to accept/decline the transaction
     * @return array
     */
    public function toTransactionOfferArray(): array
    {
        return [
            "token" => $this->id,
            "purchase" => $this->purchaseDescription,
            "price" => "$" . round($this->totalPriceUsd(), 2)
        ];
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "type" => $this->type(),
            "purchase_description" => $this->purchaseDescription,
            "account_currency_quoted" => $this->accountCurrencyQuoted,
            "account_currency_rewarded" => $this->accountCurrencyRewarded,
            "account_currency_rewarded_items" => $this->accountCurrencyRewardedForItems,
            "total_account_currency_rewarded" => $this->totalAccountCurrencyRewarded(),
            "total_usd" => $this->totalPriceUsd(),
            "open" => $this->open(),
            "created_at" => $this->createdAt,
            "paid_at" => $this->paidAt,
            "completed_at" => $this->completedAt,
            "result" => $this->result,
            "subscription_id" => $this->subscriptionId
        ];
    }

}
