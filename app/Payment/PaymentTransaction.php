<?php


namespace App\Payment;

//Holding class for a transactions details
use App\User;

class PaymentTransaction
{
    public $id = null;
    public $accountId = null;

    public $paymentProfileId = null; // May be string or int depending on vendor used
    public $type = null; // May be Card or PayPal presently

    public $externalId = null; //Vendor's ID

    public $purchaseDescription = "";
    public $accountCurrencyQuoted = 0;
    public $accountCurrencyRewarded = 0;
    public $totalPriceUsd = 0.0;
    public $recurringInterval = null;

    public $createdAt = null;
    public $completedAt = null;

    public $updated = null;

    public $status = 'unknown';
    public $open = true;

    /**
     * Produces the array used to offer a user the chance to accept/decline the transaction
     * @return array
     */
    public function toTransactionArray()
    {
        return [
            "token" => $this->id,
            "purchase" => $this->purchaseDescription,
            "price" => "$" . round($this->totalPriceUsd, 2)
        ];

    }

    public function toArray()
    {
        $array = [
            "id" => $this->id,
            "type" => $this->type,
            "purchase_description" => $this->purchaseDescription,
            "account_currency_quoted" => $this->accountCurrencyQuoted,
            "account_currency_rewarded" => $this->accountCurrencyRewarded,
            "total_usd" => $this->totalPriceUsd,
            "open" => $this->open,
            "created_at" => $this->createdAt,
            "completed_at" => $this->completedAt,
            "status" => $this->status
        ];
        if ($this->recurringInterval) $array["recurring_interval"] = $this->recurringInterval;
        return $array;
    }
}
