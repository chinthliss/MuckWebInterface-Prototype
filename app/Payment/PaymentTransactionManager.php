<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\User;
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
                                           array $items, ?int $recurringInterval) : PaymentTransaction
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

    public function createCardTransaction(User $user, Card $card, int $usdForAccountCurrency,
                                          array $items, ?int $recurringInterval) : PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);
        $transaction->paymentProfileId = $card->id;

        DB::table('billing_transactions')->insert([
            'id' => $transaction->id,
            'account_id' => $transaction->accountId,
            'paymentprofile_id' => $transaction->paymentProfileId,
            'amount_usd' => $transaction->totalPriceUsd,
            'accountcurrency_quoted' => $transaction->accountCurrencyQuoted,
            'purchase_description' => $transaction->purchaseDescription,
            'recurring_interval' => $transaction->recurringInterval,
            'created_at' => Carbon::now()
        ]);

        $clientArray = $transaction->toTransactionArray();
        if ($recurringInterval) $clientArray['note'] = "$" . round($transaction->totalPriceUsd, 2)
            . ' will be recharged every ' . $recurringInterval . ' days.';

        return $transaction;
    }

    public function getTransactionsFor(int $userId): array
    {
        $rows = DB::table('billing_transactions')->where('account_id', '=', $userId)->get();
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

    public function getTransaction(string $transactionId) : ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('id', '=', $transactionId)->first();
        if (!$row) return null;
        $transaction = new PaymentTransaction();
        $transaction->id = $row->id;
        $transaction->accountId = $row->account_id;
        if ($row->paymentprofile_id_txt) {
            $transaction->paymentProfileId = $row->paymentprofile_id_txt;
            $transaction->type = 'paypal';
        } else {
            $transaction->paymentProfileId =   $row->paymentprofile_id;
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


    public function closeTransaction(string $transactionId, string $closure_reason, int $actualAmount = null)
    {
        // Closure reason must match one of the accepted entries by the DB
        if (!in_array($closure_reason, ['fulfilled', 'user_declined', 'vendor_refused', 'expired']))
            throw new \Exception('Closure reason is unrecognised');
        DB::table('billing_transactions')->where('id', '=', $transactionId)->update([
            'result' => $closure_reason,
            'completed_at' => Carbon::now(),
            'accountcurrency_rewarded' => $actualAmount
        ]);
    }

}
