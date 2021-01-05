<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BillingSubscriptionSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     *
     * @return void
     */
    public function run()
    {
        $validAid = DB::table('accounts')->where('email', 'test@test.com')->first()->aid;
        $secondAid = DB::table('accounts')->where('email', 'secondvalid@test.com')->first()->aid;

        //Valid Owned new but unapproved Subscription
        DB::table('billing_subscriptions_combined')->insert([
            'id' => '00000000-0000-0000-0000-000000000001',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'recurring_interval' => 30,
            'created_at' => Carbon::now(),
            'status' => 'approval_pending'
        ]);

        //Valid Owned active Subscription with transaction
        DB::table('billing_subscriptions_combined')->insert([
            'id' => '00000000-0000-0000-0000-000000000002',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'recurring_interval' => 30,
            'created_at' => Carbon::now(),
            'status' => 'active'
        ]);
        DB::table('billing_transactions')->insert([
            'id' => '00000000-0000-0000-0000-0000000000s1',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'accountcurrency_rewarded' => 30,
            'purchase_description' => '30 bananas for a subscription',
            'created_at' => Carbon::now(),
            'completed_at' => Carbon::now(),
            'paid_at' => Carbon::now(),
            'result' => 'fulfilled',
            'subscription_id' => '00000000-0000-0000-0000-000000000002'
        ]);

        //Valid Owned Closed Subscription
        DB::table('billing_subscriptions_combined')->insert([
            'id' => '00000000-0000-0000-0000-000000000003',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'recurring_interval' => 30,
            'created_at' => Carbon::now(),
            'closed_at' => Carbon::now(),
            'status' => 'cancelled'
        ]);

        //Valid Unowned Subscription
        DB::table('billing_subscriptions_combined')->insert([
            'id' => '00000000-0000-0000-0000-000000000004',
            'account_id' => $secondAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'recurring_interval' => 30,
            'created_at' => Carbon::now(),
            'status' => 'approval_pending'
        ]);

    }
}
