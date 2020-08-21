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
     * Name for an item. Saved in case the name changes at some point.
     * @var String
     */
    public $name;

    /**
     * Price used for ONE item
     * @var float
     */
    public $priceUsd;

    /**
     * @var int
     */
    public $quantity;

    /**
     * How much this would be worth - IF the item rewards such.
     * Recorded since even if it doesn't earn account currency, it may earn supporter points for such
     * @var int
     */
    public $accountCurrencyValue;

    public function __construct(string $code, string $name, int $quantity, float $priceUsd,
                                int $accountCurrencyValue)
    {
        $this->code = $code;
        $this->name = $name;
        $this->quantity = $quantity;
        $this->priceUsd = $priceUsd;
        $this->accountCurrencyValue = $accountCurrencyValue;
    }

    public static function fromArray($array): PaymentTransactionItem
    {
        return new self(
            $array->code,
            $array->name,
            $array->quantity,
            $array->priceUsd,
            $array->accountCurrencyValue
        );
    }

    public function toArray(): array
    {
        return [
            "code" => $this->code,
            "name" => $this->name,
            "priceUsd" => $this->priceUsd,
            "quantity" => $this->quantity,
            "accountCurrencyValue" => $this->accountCurrencyValue
        ];
    }
}
