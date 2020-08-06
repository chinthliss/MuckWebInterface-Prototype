<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\User;
use Composer\DependencyResolver\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        if ($items) throw new \Exception("Not Implemented");

        $transaction->accountCurrencyQuoted = $this->muck->usdToAccountCurrency($usdForAccountCurrency);
        if ($transaction->accountCurrencyQuoted) {
            $transaction->totalPriceUsd += $usdForAccountCurrency;
            array_push($purchases, $transaction->accountCurrencyQuoted . ' Mako');
        }

        $transaction->purchaseDescription = implode('<br/>', $purchases);

        return $transaction;
    }

    private function insertTransactionIntoStorage(PaymentTransaction $transaction)
    {
        DB::table('billing_transactions')->insert([
            'id' => $transaction->id,
            'account_id' => $transaction->accountId,
            'paymentprofile_id' => ($transaction->type == 'card' ? $transaction->paymentProfileId : null),
            'paymentprofile_id_txt' => ($transaction->type == 'paypal' ? $transaction->paymentProfileId : null),
            'amount_usd' => $transaction->totalPriceUsd,
            'accountcurrency_quoted' => $transaction->accountCurrencyQuoted,
            'purchase_description' => $transaction->purchaseDescription,
            'recurring_interval' => $transaction->recurringInterval,
            'created_at' => Carbon::now()
        ]);
    }

    public function createCardTransaction(User $user, Card $card, int $usdForAccountCurrency,
                                          array $items, ?int $recurringInterval): PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);
        $transaction->type = 'card';
        $transaction->paymentProfileId = $card->id;

        $this->insertTransactionIntoStorage($transaction);

        return $transaction;
    }

    public function createPayPalTransaction(User $user, int $usdForAccountCurrency,
                                            array $items, ?int $recurringInterval): PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);
        $transaction->type = 'paypal';
        // PayPal payments don't get an ID until they've been through PayPal to pick an account
        $transaction->paymentProfileId = 'paypal_unattributed';

        $this->insertTransactionIntoStorage($transaction);

        return $transaction;
    }

    private function buildTransactionFromRow($row): ?PaymentTransaction
    {
        if (!$row) return null;
        $transaction = new PaymentTransaction();
        $transaction->id = $row->id;
        $transaction->accountId = $row->account_id;
        if ($row->paymentprofile_id_txt) {
            $transaction->paymentProfileId = $row->paymentprofile_id_txt;
            $transaction->type = 'paypal';
        } else {
            $transaction->paymentProfileId = $row->paymentprofile_id;
            $transaction->type = 'card';
        }
        $transaction->externalId = $row->external_id;
        $transaction->totalPriceUsd = $row->amount_usd;
        $transaction->accountCurrencyQuoted = $row->accountcurrency_quoted;
        $transaction->accountCurrencyRewarded = $row->accountcurrency_rewarded;
        $transaction->purchaseDescription = $row->purchase_description;
        $transaction->recurringInterval = $row->recurring_interval;
        $transaction->createdAt = $row->created_at;
        $transaction->completedAt = $row->completed_at;
        $transaction->status = ($row->result ?? 'open');
        $transaction->open = $row->completed_at ? false : true;
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
            $result[$row->id] = [
                'id' => $row->id,
                'type' => ($row->paymentprofile_id_txt ? 'paypal' : 'card'),
                'accountCurrency' => $row->accountcurrency_rewarded,
                'usd' => $row->amount_usd,
                'timeStamp' => $row->completed_at ?? $row->created_at,
                'status' => ($row->result ?? 'open'),
                'url' => route('accountcurrency.transaction', ["id" => $row->id])
            ];
        }
        return $result;
    }

    public function getTransaction(string $transactionId): ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('id', '=', $transactionId)->first();
        return $transaction = $this->buildTransactionFromRow($row);
    }

    public function getTransactionFromExternalId($externalId): ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('external_id', '=', $externalId)->first();
        $transaction = $this->buildTransactionFromRow($row);
        return $transaction;
    }


    public function closeTransaction(PaymentTransaction $transaction, string $closure_reason, int $actualAmount = null)
    {
        // Closure reason must match one of the accepted entries by the DB
        if (!in_array($closure_reason, ['fulfilled', 'user_declined', 'vendor_refused', 'expired']))
            throw new \Exception('Closure reason is unrecognised');
        $transaction->status = $closure_reason;
        $transaction->completedAt = Carbon::now();
        $transaction->accountCurrencyRewarded = $actualAmount;
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'result' => $transaction->status,
            'completed_at' => $transaction->completedAt,
            'accountcurrency_rewarded' => $transaction->accountCurrencyRewarded
        ]);
    }

    public function updateExternalId(PaymentTransaction $transaction, string $externalId)
    {
        $transaction->externalId = $externalId;
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'external_id' => $externalId
        ]);
    }

    public function updatePaymentProfileId(PaymentTransaction $transaction, string $externalId)
    {
        $transaction->paymentProfileId = $externalId;
        DB::table('billing_transactions')->where('id', '=', $transaction->id)->update([
            'paymentprofile_id_txt' => $externalId
        ]);
    }


}
