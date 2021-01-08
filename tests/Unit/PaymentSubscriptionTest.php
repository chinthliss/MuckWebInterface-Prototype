<?php

namespace Tests\Unit;

use App\Payment\PaymentSubscriptionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function testLastChargeAndNextChargeAreCorrect()
    {

        $interval = 30;
        $lastChargeAt = Carbon::now()->subDays($interval);
        $nextChargeAt = $lastChargeAt->copy()->addDays($interval);

        DB::table('billing_subscriptions_combined')->insert([
            'id' => 'TEST-SUBSCRIPTION',
            'account_id' => 1,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'recurring_interval' => $interval,
            'created_at' => Carbon::now()->subDays($interval),
            'status' => 'active'
        ]);

        DB::table('billing_transactions')->insert([
            'id' => 'TEST-TRANSACTION',
            'account_id' => 1,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'accountcurrency_rewarded' => 30,
            'purchase_description' => '30 tests for a subscription',
            'created_at' => $lastChargeAt,
            'completed_at' =>$lastChargeAt,
            'paid_at' => $lastChargeAt,
            'result' => 'fulfilled',
            'subscription_id' => 'TEST-SUBSCRIPTION'
        ]);

        $manager = resolve(PaymentSubscriptionManager::class);

        $subscription = $manager->getSubscription('TEST-SUBSCRIPTION');

        $this->assertTrue($subscription->lastChargeAt->diffInMinutes($lastChargeAt) < 5, 'Last charge is incorrect.');

        $this->assertTrue($subscription->nextChargeAt->diffInMinutes($nextChargeAt) < 5, 'Next charge is incorrect.');
    }
}
