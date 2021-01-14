<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\Notifications\PaymentTransactionPaid;
use App\User;
use Error;
use Exception;
use Illuminate\Database\Query\Builder;
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

    /**
     * @return Builder
     */
    private function storageTable(): Builder
    {
        return DB::table('billing_transactions');
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
            'subscription_id' => $transaction->subscriptionId,
            'created_at' => Carbon::now()
        ];
        if ($transaction->items) $row['items_json'] = json_encode(array_map(function ($item) {
            return $item->toArray();
        }, $transaction->items));
        $this->storageTable()->insert($row);
    }

    public function createTransaction(User $user, string $vendor, string $vendorProfileId,
                                      int $usdForAccountCurrency, array $items, string $subscriptionId = null): PaymentTransaction
    {
        $purchases = [];

        $transaction = new PaymentTransaction();
        $transaction->accountId = $user->getAid();
        $transaction->id = Str::uuid();
        $transaction->vendor = $vendor;
        $transaction->vendorProfileId = $vendorProfileId;

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

        if ($subscriptionId) $transaction->subscriptionId = $subscriptionId;

        $this->insertTransactionIntoStorage($transaction);

        return $transaction;
    }

    private function buildTransactionFromRow($row): ?PaymentTransaction
    {
        if (!$row) return null;
        $transaction = new PaymentTransaction();
        $transaction->id = $row->id;
        $transaction->accountId = $row->account_id;
        $transaction->vendor = $row->vendor;
        $transaction->vendorProfileId = $row->vendor_profile_id;
        $transaction->vendorTransactionId = $row->vendor_transaction_id;
        $transaction->subscriptionId = $row->subscription_id;
        $transaction->accountCurrencyPriceUsd = $row->amount_usd;
        $transaction->accountCurrencyQuoted = $row->accountcurrency_quoted;
        $transaction->accountCurrencyRewarded = $row->accountcurrency_rewarded;
        $transaction->accountCurrencyRewardedForItems = $row->accountcurrency_rewarded_items;
        $transaction->purchaseDescription = $row->purchase_description;
        $transaction->itemPriceUsd = $row->amount_usd_items;
        //$transaction->items = json_decode($row->items_json);
        if ($row->items_json) {
            foreach (json_decode($row->items_json) as $itemArray) {
                array_push($transaction->items, PaymentTransactionItem::fromArray($itemArray));
            }
        }
        $transaction->createdAt = $row->created_at ? new Carbon($row->created_at) : null;
        $transaction->completedAt = $row->completed_at ? new Carbon($row->completed_at) : null;
        $transaction->paidAt =  $row->paid_at ? new Carbon($row->paid_at) : null;
        $transaction->result = $row->result;
        return $transaction;
    }

    /**
     * @param int $userId
     * @return PaymentTransaction[]
     */
    public function getTransactionsFor(int $userId): array
    {
        $rows = $this->storageTable()
            ->where('account_id', '=', $userId)
            ->orderBy('created_at')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $transaction = $this->buildTransactionFromRow($row);
            $result[$transaction->id] = $transaction;
        }
        return $result;
    }

    public function getTransaction(string $transactionId): ?PaymentTransaction
    {
        $row = $this->storageTable()->where('id', '=', $transactionId)->first();
        return $this->buildTransactionFromRow($row);
    }

    public function getTransactionFromExternalId($externalId): ?PaymentTransaction
    {
        $row = $this->storageTable()->where('vendor_transaction_id', '=', $externalId)->first();
        return $this->buildTransactionFromRow($row);
    }

    /**
     * @param $subscriptionId
     * @param Carbon|null $after Cut off date
     * @return PaymentTransaction[]
     */
    public function getTransactionsFromSubscriptionId($subscriptionId, Carbon $after = null): array
    {
        $rows = $this->storageTable()->where('subscription_id', '=', $subscriptionId)->get();
        $result = [];
        foreach ($rows as $row) {
            $transaction = $this->buildTransactionFromRow($row);
            if ($after && $transaction->createdAt < $after) continue;
            $result[$transaction->id] = $transaction;
        }
        return $result;
    }

    /**
     * @param PaymentTransaction $transaction
     * @param string $closureReason
     * @throws Exception
     */
    public function closeTransaction(PaymentTransaction $transaction, string $closureReason)
    {
        // Closure reason must match one of the accepted entries by the DB
        if (!in_array($closureReason, ['fulfilled', 'user_declined', 'vendor_refused', 'expired']))
            throw new Exception('Closure reason is unrecognised');
        $transaction->result = $closureReason;
        $transaction->completedAt = Carbon::now();
        $this->storageTable()->where('id', '=', $transaction->id)->update([
            'result' => $transaction->result,
            'completed_at' => $transaction->completedAt,
            'accountcurrency_rewarded' => $transaction->accountCurrencyRewarded,
            'accountcurrency_rewarded_items' => $transaction->accountCurrencyRewardedForItems
        ]);
    }

    public function setPaid(PaymentTransaction $transaction)
    {
        $transaction->paidAt = Carbon::now();
        $this->storageTable()->where('id', '=', $transaction->id)->update([
            'paid_at' => $transaction->paidAt
        ]);
        $user = User::find($transaction->accountId);
        $user->notify(new PaymentTransactionPaid($transaction));
    }

    public function updateVendorTransactionId(PaymentTransaction $transaction, string $vendorTransactionId)
    {
        $transaction->vendorTransactionId = $vendorTransactionId;
        $this->storageTable()->where('id', '=', $transaction->id)->update([
            'vendor_transaction_id' => $vendorTransactionId
        ]);
    }

    public function updateVendorProfileId(PaymentTransaction $transaction, string $vendorProfileId)
    {
        $transaction->vendorProfileId = $vendorProfileId;
        $this->storageTable()->where('id', '=', $transaction->id)->update([
            'vendor_profile_id' => $vendorProfileId
        ]);
    }

    /**
     * Closes off items that the user never accepted
     */
    public function closePending()
    {
        $cutOff = Carbon::now()->subMinutes(30);
        $rows = $this->storageTable()
            ->whereNull('result')
            ->whereNull('paid_at')
            ->whereNull('completed_at')
            ->where('created_at', '<', $cutOff)
            ->get();
        foreach ($rows as $row) {
            $transaction = $this->buildTransactionFromRow($row);
            if ($transaction->open()) {
                Log::info("Closing Payment Transaction " . $transaction->id
                    . " created at " . $transaction->createdAt . " because user never accepted it.");
                $this->closeTransaction($transaction, 'user_declined');
            }
        }
    }

    public function chargeTransaction(PaymentTransaction $transaction)
    {
        switch($transaction->vendor) {
            case 'authorizenet':
                $user = User::find($transaction->accountId);
                $cardPaymentManager = resolve('App\Payment\CardPaymentManager');
                $card = $cardPaymentManager->getCardFor($user, $transaction->vendorProfileId);
                try {
                    $cardPaymentManager->chargeCardFor($user, $card, $transaction);
                } catch (Exception $e) {
                    Log::info("Error during chargeTransaction authorizenet payment: " . $e);
                    throw $e;
                }
                break;
            default:
                Log::error("Attempt to charge transaction {$transaction->id} with an unknown or non-charging vendor: {$transaction->vendor}");
                throw new Error("Transaction isn't chargeable - potentially because it's handled externally.");
        }

    }

    /**
     * @param PaymentTransaction $transaction
     */
    public function fulfillTransaction(PaymentTransaction $transaction)
    {
        Log::debug("PaymentTransaction#" . $transaction->id . " - Being fulfilled.");

        //Actual fulfilment is done by the MUCK still, due to ingame triggers
        $muck = resolve('App\Muck\MuckConnection');

        if ($transaction->accountCurrencyQuoted) {
            $transaction->accountCurrencyRewarded = $muck->adjustAccountCurrency(
                $transaction->accountId,
                $transaction->accountCurrencyPriceUsd,
                $transaction->accountCurrencyQuoted,
                ''
            );
        }

        if ($transaction->items) {
            $transaction->accountCurrencyRewardedForItems = 0;
            foreach ($transaction->items as $item) {
                $transaction->accountCurrencyRewardedForItems += $muck->rewardItem(
                    $transaction->accountId,
                    $item->priceUsd,
                    $item->accountCurrencyValue,
                    $item->code
                );
            }
        }
    }
}
