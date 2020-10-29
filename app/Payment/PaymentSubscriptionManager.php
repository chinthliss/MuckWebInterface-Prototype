<?php


namespace App\Payment;

use App\Muck\MuckConnection;
use App\User;
use \Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
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
        $subscription->vendorSubscriptionId = $row->vendor_profile_id;
        $subscription->vendorSubscriptionPlanId = $row->vendor_subscription_plan_id;
        $subscription->amountUsd = $row->amount_usd;
        $subscription->recurringInterval = $row->recurring_interval;
        $subscription->createdAt = $row->created_at;
        $subscription->nextChargeAt = $row->next_charge_at;
        $subscription->closedAt = $row->closed_at;
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
        $row = $this->storageTable()->where('id', '=', $subscriptionId)->first();
        return $this->buildSubscriptionFromRow($row);
    }

    public function getSubscriptionsFor(int $userId): array
    {
        $rows = $this->storageTable()
            ->where('account_id', '=', $userId)
            ->orderBy('created_at')
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $subscription = $this->buildSubscriptionFromRow($row);
            $result[$subscription->id] = [
                'id' => $subscription->id,
                'type' => $subscription->type(),
                'amount_usd' => $subscription->amountUsd,
                'recurring_interval' => $subscription->recurringInterval,
                'created' => $subscription->createdAt,
                'closed' => $subscription->closedAt,
                'next_charge' => $subscription->nextChargeAt,
                'status' => $subscription->status,
                'url' => route('accountcurrency.subscription', ["id" => $subscription->id])
            ];
        }
        return $result;
    }

    public function closeSubscription(PaymentSubscription $subscription, string $closureReason)
    {
        if (!in_array($closureReason, ['fulfilled', 'user_declined', 'cancelled', 'expired']))
            throw new Exception('Closure reason is unrecognised');
        $subscription->status = $closureReason;
        $subscription->closedAt = Carbon::now();
        $subscription->nextChargeAt = null;
        $this->storageTable()->where('id', '=', $subscription->id)->update([
            'status' => $subscription->status,
            'closed_at' => $subscription->closedAt,
            'next_charge_at' => $subscription->nextChargeAt
        ]);
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
