<?php


namespace App\Payment;

/**
 * Holds the information for an item bought as part of a transaction
 * @package App\Payment
 */
class PaymentTransactionItem
{
    /**
     * Short code for the item
     * @var String
     */
    public $code;

    /**
     * Price used for ONE item
     * @var Float
     */
    public $priceUsd;

    /**
     * @var Integer
     */
    public $quantity;

    public function __construct(string $code, int $quantity, float $priceUsd)
    {
        $this->code = $code;
        $this->quantity = $quantity;
        $this->priceUsd = $priceUsd;
    }

    public function toArray() : array
    {
        return [
            "code" => $this->code,
            "name" => $this->name,
            "priceUsd" => $this->priceUsd,
            "quantity" => $this->quantity
        ];
    }
}
