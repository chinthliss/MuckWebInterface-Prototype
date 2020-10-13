<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentTransactionManager
{
    /**
     * @var MuckConnection
     */
    protected $muck;

    public function __construct(MuckConnection $muck)
    {
        $this->muck = $muck;
    }

    // Handles the shared parts
    private function createStubTransaction(User $user, int $usdForAccountCurrency,
                                           array $items, ?int $recurringInterval): PaymentTransaction
    {
        $purchases = [];

        $transaction = new PaymentTransaction();
        $transaction->accountId = $user->getAid();
        $transaction->id = Str::uuid();


        if ($recurringInterval) $transaction->recurringInterval = $recurringInterval;

        $transaction->accountCurrencyQuoted = $this->muck->usdToAccountCurrency($usdForAccountCurrency);
        if ($transaction->accountCurrencyQuoted) {
            $transaction->accountCurrencyPriceUsd = $usdForAccountCurrency;
            array_push($purchases, $transaction->accountCurrencyQuoted . ' Mako');
        }

        if ($items) {
            $itemCatalogue = resolve('App\Payment\PaymentTransactionItemCatalogue')->itemsCatalogue();
            $itemsRecord = [];
            foreach ($items as $itemCode) {
                if (!array_key_exists($itemCode, $itemCatalogue)) {
                    Log::error("Attempt made to purchase non-existent billing item with itemCode " . $itemCode);
                } else {
                    $item = new PaymentTransactionItem(
                        $itemCode,
                        $itemCatalogue[$itemCode]['name'],
                        1,
                        $itemCatalogue[$itemCode]['amountUsd'],
                        $this->muck->usdToAccountCurrency($itemCatalogue[$itemCode]['amountUsd'])
                    );
                    $transaction->itemPriceUsd += $item->priceUsd;
                    array_push($itemsRecord, $item);
                    array_push($purchases, $item->name);
                }
            }
            $transaction->items = $itemsRecord;
        }

        $transaction->purchaseDescription = implode('<br/>', $purchases);

        return $transaction;
    }

    private function insertTransactionIntoStorage(PaymentTransaction $transaction)
    {
        $row = [
            'id' => $transaction->id,
            'account_id' => $transaction->accountId,
            'vendor' => $transaction->vendor,
            'vendor_profile_id' => $transaction->vendorProfileId,
            'vendor_transaction_id' => $transaction->vendorTransactionId,
            'amount_usd' => $transaction->accountCurrencyPriceUsd,
            'amount_usd_items' => $transaction->itemPriceUsd,
            'accountcurrency_quoted' => $transaction->accountCurrencyQuoted,
            'purchase_description' => $transaction->purchaseDescription,
            'recurring_interval' => $transaction->recurringInterval,
            'created_at' => Carbon::now()
        ];
        if ($transaction->items) $row['items_json'] = json_encode(array_map(function ($item) {
            return $item->toArray();
        }, $transaction->items));
        DB::table('billing_transactions')->insert($row);
    }

    public function createCardTransaction(User $user, Card $card, int $usdForAccountCurrency,
                                          array $items, ?int $recurringInterval): PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);
        $transaction->vendor = 'authorizenet';
        $transaction->type = 'Card';
        $transaction->vendorProfileId  = $card->id;

        $this->insertTransactionIntoStorage($transaction);

        return $transaction;
    }

    public function createPayPalTransaction(User $user, int $usdForAccountCurrency,
                                            array $items, ?int $recurringInterval): PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);
        $transaction->vendor = 'paypal';
        $transaction->type = 'Paypal';
        // PayPal payments don't get an ID until they've been through PayPal to pick an account
        $transaction->vendorProfileId = 'paypal_unattributed';

        $this->insertTransactionIntoStorage($transaction);

        return $transaction;
    }

    private function buildTransactionFromRow($row): ?PaymentTransaction
    {
        if (!$row) return null;
        $transaction = new PaymentTransaction();
        $transaction->id = $row->id;
        $transaction->accountId = $row->account_id;
        $transaction->type = ($row->vendor == 'paypal' ? 'Paypal' : 'Card');
        $transaction->vendor = $row->vendor;
        $transaction->vendorProfileId = $row->vendor_profile_id;
        $transaction->vendorTransactionId = $row->vendor_transaction_id;
        $transaction->accountCurrencyPriceUsd = $row->amount_usd;
        $transaction->accountCurrencyQuoted = $row->accountcurrency_quoted;
        $transaction->accountCurrencyRewarded = $row->accountcurrency_rewarded;
        $transaction->accountCurrencyRewardedForItems = $row->accountcurrency_rewarded_items;
        $transaction->purchaseDescription = $row->purchase_description;
        $transaction->recurringInterval = $row->recurring_interval;
        $transaction->itemPriceUsd = $row->amount_usd_items;
        //$transaction->items = json_decode($row->items_json);
        if ($row->items_json) {
            foreach (json_decode($row->items_json) as $itemArray) {
                array_push($transaction->items, PaymentTransactionItem::fromArray($itemArray));
            }
        }
        $transaction->createdAt = $row->created_at;
        $transaction->completedAt = $row->completed_at;
        $transaction->result = $row->result;
        return $transaction;
    }

    public function getTransactionsFor(int $userId): array
    {
        $rows = DB::table('billing_transactions')
            ->where('account_id', '=', $userId)
            ->orderBy('created_at')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $transaction = $this->buildTransactionFromRow($row);
            $result[$transaction->id] = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'usd' => $transaction->totalPriceUsd(),
                'accountCurrency' => $transaction->totalAccountCurrencyRewarded(),
                'items' => count($transaction->items),
                'timeStamp' => $transaction->completedAt ?? $transaction->createdAt,
                'result' => $transaction->result,
                'url' => route('accountcurrency.transaction', ["id" => $transaction->id])
            ];
        }
        return $result;
    }

    public function getTransaction(string $transactionId): ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('id', '=', $transactionId)->first();
        return $this->buildTransactionFromRow($row);
    }

    public function getTransactionFromExternalId($externalId): ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('vendor_transaction_id', '=', $externalId)->first();
        return $this->buildTransactionFromRow($row);
    }


    public function closeTransaction(PaymentTransaction $transaction, string $closure_reason)
    {
        // Closure reason must match one of the accepted entries by the DB
        if (!in_array($closure_reason, ['fulfilled', 'user_declined', 'vendor_refused', 'expired']))
            throw new Exception('Closure reason is unrecognised');
        $transaction->result = $closure_reason;
        $transaction->completedAt = Carbon::now();
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'result' => $transaction->result,
            'completed_at' => $transaction->completedAt,
            'accountcurrency_rewarded' => $transaction->accountCurrencyRewarded,
            'accountcurrency_rewarded_items' => $transaction->accountCurrencyRewardedForItems
        ]);
    }

    public function setPaid(PaymentTransaction $transaction)
    {
        $transaction->paidAt = Carbon::now();
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'paid_at' => $transaction->paidAt
        ]);
    }

    public function updateVendorTransactionId(PaymentTransaction $transaction, string $vendorTransactionId)
    {
        $transaction->vendorTransactionId = $vendorTransactionId;
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'vendor_transaction_id' => $vendorTransactionId
        ]);
    }

    public function updateVendorProfileId(PaymentTransaction $transaction, string $vendorProfileId)
    {
        $transaction->vendorProfileId = $vendorProfileId;
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'vendor_profile_id' => $vendorProfileId
        ]);
    }


}
