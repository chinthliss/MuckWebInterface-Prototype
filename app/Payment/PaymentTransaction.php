<?php


namespace App\Payment;

//Holding class for a transactions details

use Illuminate\Support\Carbon;

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

    /**
     * @var Carbon|null
     */
    public $createdAt = null;

    /**
     * @var Carbon|null
     */
    public $paidAt = null;

    /**
     * @var Carbon|null
     */
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
        if ($this->vendor === 'patreon') return 'Patreon';
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
        $array = [
            "id" => $this->id,
            "account_id" => $this->accountId,
            "type" => $this->type(),
            "purchase_description" => $this->purchaseDescription,
            "account_currency_quoted" => $this->accountCurrencyQuoted,
            "account_currency_rewarded" => $this->accountCurrencyRewarded,
            "account_currency_rewarded_items" => $this->accountCurrencyRewardedForItems,
            "total_account_currency_rewarded" => $this->totalAccountCurrencyRewarded(),
            "total_usd" => $this->totalPriceUsd(),
            "items" => count($this->items),
            "open" => $this->open(),
            "created_at" => $this->createdAt,
            "paid_at" => $this->paidAt,
            "completed_at" => $this->completedAt,
            "result" => $this->result,
            "subscription_id" => $this->subscriptionId,
            "url" => route('accountcurrency.transaction', ['id' => $this->id])
        ];
        if ($this->subscriptionId && $this->vendor != 'patreon')
            $array['subscription_url'] = route('accountcurrency.subscription', ["id" => $this->subscriptionId]);
        return $array;
    }

}
