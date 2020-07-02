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

    public function createCardTransaction(User $user, Card $card, int $usdForAccountCurrency,
                                          array $items, ?int $recurringInterval) : PaymentTransaction
    {
        $transaction = $this->createStubTransaction($user, $usdForAccountCurrency, $items, $recurringInterval);

        $transaction->paymentId = $card->id;

        DB::table('billing_transactions')->insert([
            'id' => $transaction->id,
            'account_id' => $transaction->accountId,
            'paymentprofile_id' => $transaction->paymentId,
            'amount_usd' => $transaction->totalPriceUsd,
            'amount_accountcurrency' => $transaction->accountCurrencyRewarded,
            'purchase_description' => $transaction->purchaseDescription,
            'recurring_interval' => $transaction->recurringInterval,
            'created_at' => Carbon::now()
        ]);

        $clientArray = $transaction->toClientArray();
        if ($recurringInterval) $clientArray['note'] = "$" . round($transaction->totalPriceUsd, 2)
            . ' will be recharged every ' . $recurringInterval . ' days.';

        return $transaction;
    }

    private function createStubTransaction(User $user, int $usdForAccountCurrency,
                                           array $items, ?int $recurringInterval) : PaymentTransaction
    {
        $purchases = [];

        $transaction = new PaymentTransaction();
        $transaction->accountId = $user->getAid();
        $transaction->id = Str::uuid();

        if ($recurringInterval) $transaction->recurringInterval = $recurringInterval;

        if ($items) throw new \Exception("Not Implemented");

        $transaction->accountCurrencyRewarded = $this->muck->usdToAccountCurrency($usdForAccountCurrency);
        if ($transaction->accountCurrencyRewarded) {
            $transaction->totalPriceUsd += $usdForAccountCurrency;
            array_push($purchases, $transaction->accountCurrencyRewarded . ' Mako');
        }

        $transaction->purchaseDescription = implode('<br/>', $purchases);

        return $transaction;
    }

    public function getTransaction(string $transactionId) : ?PaymentTransaction
    {
        $row = DB::table('billing_transactions')->where('id', '=', $transactionId)->first();
        if (!$row) return null;
        $transaction = new PaymentTransaction();
        $transaction->id = $row->id;
        $transaction->accountId = $row->account_id;
        if ($row->paymentprofile_id_txt) {
            $transaction->paymentId = $row->paymentprofile_id_txt;
            $transaction->type = 'paypal';
        } else {
            $transaction->paymentId =   $row->paymentprofile_id;
            $transaction->type = 'card';
        }
        $transaction->externalId = $row->external_id;
        $transaction->totalPriceUsd = $row->amount_usd;
        $transaction->accountCurrencyRewarded = $row->amount_accountcurrency;
        $transaction->purchaseDescription = $row->purchase_description;
        $transaction->recurringInterval = $row->recurring_interval;
        $transaction->open = $row->result == null;
        return $transaction;
    }


    public function closeTransaction(string $transactionId, string $closure_reason)
    {
        // Closure reason must match one of the accepted entries by the DB
        if (!in_array($closure_reason, ['fulfilled', 'user_declined', 'vendor_refused', 'expired']))
            throw new \Exception('Closure reason is unrecognised');
        DB::table('billing_transactions')->where('id', '=', $transactionId)->update([
            'result' => $closure_reason,
            'completed_at' => Carbon::now()
        ]);
    }

}
