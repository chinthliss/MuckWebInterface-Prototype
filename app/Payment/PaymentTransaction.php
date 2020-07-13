<?php


namespace App\Payment;

//Holding class for a transactions details
use App\User;

class PaymentTransaction
{
    public $id = null;
    public $accountId = null;

    public $paymentId = null; // May be string or int depending on vendor used
    public $type = null; // May be Card or PayPal presently

    public $externalId = null;

    public $purchaseDescription = "";
    public $accountCurrencyQuoted = 0;
    public $accountCurrencyRewarded = 0;
    public $totalPriceUsd = 0.0;
    public $recurringInterval = null;

    public $open = false;

    public function toClientArray()
    {
        return [
            "token" => $this->id,
            "purchase" => $this->purchaseDescription,
            "price" => "$" . round($this->totalPriceUsd, 2)
        ];

    }
}
