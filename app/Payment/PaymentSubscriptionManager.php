<?php


namespace App\Payment;

use App\Muck\MuckConnection;
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

    /**
     * @var bool
     */
    private $processSubscriptionPayments = false;

    public function __construct(MuckConnection $muck, bool $processSubscriptionPayments)
    {
        $this->muck = $muck;
        $this->processSubscriptionPayments = $processSubscriptionPayments;
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
            ->select(['subscription_id', DB::raw('MAX(paid_at) as last_charge_at')])
            ->groupBy('subscription_id');

        return $this->storageTable()
            ->leftJoinSub($transactionJoin, 'transactions', 'transactions.subscription_id', '=', 'billing_subscriptions_combined.id');
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
            ->where('status', '=', 'active')
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

    function processSubscriptions()
    {
        Log::debug("PaymentSubscription - processSubscriptions started");
        $subscriptions = $this->getSubscriptionsDuePayment();
        if (!$this->processSubscriptionPayments) Log::info("PaymentSubscription - Processing is disabled, so only checking for eligibility.");
        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription);
        }
        Log::debug("PaymentSubscription - processSubscriptions finished");
    }

    /**
     * Runs either after a subscription is created to attempt a payment or through regular processing
     * @param PaymentSubscription $subscription
     */
    function processSubscription(PaymentSubscription $subscription)
    {
        if ($subscription->vendor == 'paypal') return; // Done externally.

        Log::info("PaymentSubscription - Payment for {$subscription->id} which has a last payment date of: "
            . ($subscription->lastChargeAt ?? 'None'));

        $transactionManager = resolve(PaymentTransactionManager::class);
        $transactions = $transactionManager->getTransactionsSinceLastPaymentForSubscription($subscription);

        $lastAttempt = null;
        foreach ($transactions as $transaction) {
            if (!$lastAttempt || $transaction->createdAt > $lastAttempt) $lastAttempt = $transaction->createdAt;
        }

        if ($lastAttempt && $lastAttempt->diffInHours(Carbon::now()) < 6) {
            Log::warning("PaymentSubscription - Skipped {$subscription->id} due to recent previous attempt at {$lastAttempt}.");
            return;
        }

        if (!$this->processSubscriptionPayments) {
            Log::info("PaymentSubscription - Skipped {$subscription->id} due to reward processing turned off.");
            return;
        }

        //Start some sort of payment off
        $transaction = $this->createTransactionForSubscription($subscription);

        try {
            $transactionManager->chargeTransaction($transaction);
        } catch (Exception $e) {
            Log::info("Error during subscription card payment: " . $e);
            $transactionManager->closeTransaction($transaction, 'vendor_refused');
        }

        if ($transaction->paid()) {
            $this->processPaidSubscriptionTransaction($subscription, $transaction);
        } else {
            if (count($transactions) > 4) {
                Log::warning("Suspended subscription {$subscription->id} due to too many failures.");
                $this->suspendSubscription($subscription);
            }
        }
    }

    // Processes a payment that has occurred against a subscription.
    public function processPaidSubscriptionTransaction(PaymentSubscription $subscription, PaymentTransaction $transaction)
    {
        Log::debug("Subscription#" . $subscription->id
            . " - Processing a payment from vendor " . $transaction->vendor
            . ", using Transaction#" . $transaction->id);

        if ($transaction->accountCurrencyPriceUsd != $subscription->amountUsd)
            Log::warning("Attempt to pay the wrong amount (" . $transaction->accountCurrencyPriceUsd
                . ") against subscription#" . $subscription->id
                . " which has an amount of " . $subscription->amountUsd);

        if (!$transaction->open()) throw new Error("Subscription#" . $subscription->id
            . " tried to fulfill the closed transaction: " . $transaction->id);

        if (!$transaction->paid()) throw new Error("Subscription#" . $subscription->id
            . " tried to fulfill the paid transaction: " . $transaction->id);

        $transactionManager = resolve(PaymentTransactionManager::class);
        $transactionManager->fulfillTransaction($transaction);
        $transactionManager->closeTransaction($transaction, 'fulfilled');
    }

    public function createTransactionForSubscription(PaymentSubscription  $subscription)
    {
        $transactionManager = resolve(PaymentTransactionManager::class);
        $user = User::find($subscription->accountId);
        return $transactionManager->createTransactionForDirectSupport($user, $subscription->vendor, $subscription->vendorProfileId,
            $subscription->amountUsd, [], $subscription->id);
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
