<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\Notifications\PaymentTransactionPaid;
use App\User;
use Error;
use \Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentSubscriptionManager
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
        return DB::table('billing_subscriptions_combined');
    }

    private function storageTableWithTransactionJoin(): Builder
    {
        $transactionJoin = DB::table('billing_transactions')
            ->select('subscription_id', DB::raw('MAX(paid_at) as last_charge_at'))
            ->groupBy('subscription_id');

        return $this->storageTable()
            ->leftJoinSub($transactionJoin, 'transactions','transactions.subscription_id', '=', 'billing_subscriptions_combined.id');
    }

    public function insertSubscriptionIntoStorage(PaymentSubscription $subscription)
    {
        $row = [
            'id' => $subscription->id,
            'account_id' => $subscription->accountId,
            'vendor' => $subscription->vendor,
            'vendor_profile_id' => $subscription->vendorProfileId,
            'vendor_subscription_plan_id' => $subscription->vendorSubscriptionPlanId,
            'amount_usd' => $subscription->amountUsd,
            'recurring_interval' => $subscription->recurringInterval,
            'created_at' => Carbon::now(),
            'status' => $subscription->status
        ];
        $this->storageTable()->insert($row);
    }

    private function buildSubscriptionFromRow($row): ?PaymentSubscription
    {
        if (!$row) return null;
        $subscription = new PaymentSubscription();
        $subscription->id = $row->id;
        $subscription->accountId = $row->account_id;
        $subscription->vendor = $row->vendor;
        $subscription->vendorProfileId = $row->vendor_profile_id;
        $subscription->vendorSubscriptionId = $row->vendor_subscription_id;
        $subscription->vendorSubscriptionPlanId = $row->vendor_subscription_plan_id;
        $subscription->amountUsd = $row->amount_usd;
        $subscription->recurringInterval = $row->recurring_interval;
        $subscription->createdAt = new Carbon($row->created_at);
        if (property_exists($row, 'last_charge_at') && $row->last_charge_at) {
            $subscription->lastChargeAt = new Carbon($row->last_charge_at);
            $subscription->nextChargeAt = $subscription->lastChargeAt->copy()->addDays($subscription->recurringInterval);
        }
        $subscription->closedAt = $row->closed_at ? new Carbon($row->closed_at) : null;
        $subscription->status = $row->status;
        return $subscription;
    }

    public function createSubscription(User $user, string $vendor,
                                       string $vendorProfileId, $vendorSubscriptionPlanId,
                                       int $amountUsd, int $recurringInterval): PaymentSubscription
    {
        $subscription = new PaymentSubscription();
        $subscription->accountId = $user->getAid();
        $subscription->id = Str::uuid();
        $subscription->vendor = $vendor;
        $subscription->vendorProfileId = $vendorProfileId;
        $subscription->vendorSubscriptionPlanId = $vendorSubscriptionPlanId;
        $subscription->amountUsd = $amountUsd;
        $subscription->recurringInterval = $recurringInterval;
        $subscription->status = 'approval_pending';

        $this->insertSubscriptionIntoStorage($subscription);

        return $subscription;

    }

    public function getSubscription(string $subscriptionId): ?PaymentSubscription
    {
        $row = $this->storageTableWithTransactionJoin()->where('id', '=', $subscriptionId)->first();
        return $this->buildSubscriptionFromRow($row);
    }

    /**
     * @param int $userId
     * @return PaymentSubscription[]
     */
    public function getSubscriptionsFor(int $userId): array
    {
        $rows = $this->storageTableWithTransactionJoin()
            ->where('account_id', '=', $userId)
            ->orderBy('created_at')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $subscription = $this->buildSubscriptionFromRow($row);
            $result[$subscription->id] = $subscription;
        }
        return $result;
    }

    public function getSubscriptionFromVendorId(string $subscriptionVendorId): ?PaymentSubscription
    {
        $row = $this->storageTableWithTransactionJoin()
            ->where('vendor_subscription_id', '=', $subscriptionVendorId)
            ->first();
        return $this->buildSubscriptionFromRow($row);
    }

    public function getSubscriptions(): Collection
    {
        $allSubscriptions = $this->storageTableWithTransactionJoin()->get();
        return $allSubscriptions->map(function ($row) {
            return $this->buildSubscriptionFromRow($row);
        });
    }

    /**
     * @return PaymentSubscription[]
     */
    public function getSubscriptionsDuePayment(): array
    {
        $subscriptions = [];

        $rows = $this->storageTableWithTransactionJoin()
            ->where('status', '=',  'active')
            ->get();

        foreach ($rows as $row) {
            $subscription = $this->buildSubscriptionFromRow($row);
            if ($subscription->nextChargeAt < Carbon::now()) array_push($subscriptions, $subscription);
        }
        return $subscriptions;
    }

    public function closeSubscription(PaymentSubscription $subscription, string $closureReason)
    {
        if (!in_array($closureReason, ['fulfilled', 'user_declined', 'cancelled', 'expired']))
            throw new Exception('Closure reason is unrecognised');
        // If this is a paypal subscription, we need to notify them
        if ($subscription->vendor === 'paypal') {
            $paypalManager = resolve(PayPalManager::class);
            $paypalManager->cancelSubscription($subscription);
        }
        $subscription->status = $closureReason;
        $subscription->closedAt = Carbon::now();
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'status' => $subscription->status,
            'closed_at' => $subscription->closedAt
        ]);
    }

    public function suspendSubscription(PaymentSubscription $subscription)
    {
        $subscription->status = 'suspended';
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'status' => $subscription->status
        ]);
    }

    // Processes a payment against a subscription.
    // If a transaction is passed it will use that otherwise one will be created
    public function processSubscriptionPayment(PaymentSubscription $subscription, float $amountUsd, string $vendor,
                                               string $vendorTransactionId, PaymentTransaction $transaction = null)
    {
        Log::debug("Subscription#" . $subscription->id . " - Processing a payment from vendor " . $vendor);

        if ($amountUsd != $subscription->amountUsd)
            Log::warning("Attempt to pay the wrong amount (" . $amountUsd
                . ") against subscription#" . $subscription->id
                . " which has an amount of " . $subscription->amountUsd);

        $user = User::find($subscription->accountId);
        $transactionManager = resolve(PaymentTransactionManager::class);

        if (!$transaction) {
            //Check if it maybe exists first
            $transaction = $transactionManager->getTransactionFromExternalId($vendorTransactionId);
            if (!$transaction) {
                $transaction = $transactionManager->createTransaction($user, $vendor, $subscription->vendorProfileId,
                    $amountUsd, [], $subscription->id);
                $transactionManager->updateVendorTransactionId($transaction, $vendorTransactionId);
            }
        }

        if (!$transaction->open()) throw new Error("Subscription#" . $subscription->id
            . " tried to fulfill the closed transaction: " . $transaction->id);

        Log::debug("Subscription - Using transaction " . $transaction->id);
        $transactionManager->setPaid($transaction);
        $user->notify(new PaymentTransactionPaid($transaction));
        $transactionManager->fulfillTransaction($transaction);
        $transactionManager->closeTransaction($transaction, 'fulfilled');

    }

    public function updateVendorProfileId(PaymentSubscription $subscription, string $vendorProfileId)
    {
        $subscription->vendorProfileId = $vendorProfileId;
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'vendor_profile_id' => $vendorProfileId
        ]);
    }

    public function updateVendorSubscriptionId(PaymentSubscription $subscription, string $vendorSubscriptionId)
    {
        $subscription->vendorSubscriptionId = $vendorSubscriptionId;
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'vendor_subscription_id' => $vendorSubscriptionId
        ]);
    }

    /**
     * Closes off items that the user never accepted
     */
    public function closePending()
    {
        $cutOff = Carbon::now()->subMinutes(30);
        $rows = $this->storageTable()
            ->where('status', '=', 'approval_pending')
            ->whereDate('created_at', '<', $cutOff)
            ->get();
        foreach ($rows as $row) {
            $subscription = $this->buildSubscriptionFromRow($row);
            if ($subscription->open()) {
                Log::info("Closing Payment Subscription " . $subscription->id
                    . " created at " . $subscription->createdAt . " because user never accepted it.");
                $this->closeSubscription($subscription, 'user_declined');
            }
        }
    }

    /**
     * @param PaymentSubscription $subscription
     */
    public function setSubscriptionAsActive(PaymentSubscription $subscription)
    {
        $subscription->status = 'active';
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'status' => 'active',
        ]);
    }
}
