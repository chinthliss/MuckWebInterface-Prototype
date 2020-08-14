<?php


namespace App\Payment;

use App\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentTransactionItemCatalogue
{
    private $itemsCatalogue;

    public function itemsCatalogue()
    {
        if (!$this->itemsCatalogue) {
            $this->itemsCatalogue = [];
            $entries = DB::table('billing_itemcatalogue')->get();
            foreach ($entries as $row) {
                $this->itemsCatalogue[$row->code] = [
                    'name' => $row->name,
                    'description' => $row->description,
                    'amountUsd' => $row->amount_usd,
                    'supporter' => ($row->supporter == 1)
                ];
            }
        }
        return $this->itemsCatalogue;
    }

    /**
     * Simple function to add individual tests to - until something more elaborate is needed
     */
    public function isEligibleFor(User $user, string $itemCode): bool
    {
        return true;
    }

    public function getEligibleItemsFor(User $user): array
    {
        $items = [];
        foreach ($this->itemsCatalogue() as $code => $item) {
            if ($this->isEligibleFor($user, $code)) {
                array_push($items, $code);
            }
        }
        return $items;
    }

    public function itemCodeToArray(string $itemCode)
    {
        if (!array_key_exists($itemCode, $this->itemsCatalogue()))
            throw new Exception("Invalid item code - " . $itemCode);

        $item = $this->itemsCatalogue()[$itemCode];
        return [
            "code" => $itemCode,
            "name" => $item['name'],
            "description" => $item['description'],
            "amountUsd" => $item['amountUsd'],
            "supporter" => $item['supporter']
        ];
    }
}
