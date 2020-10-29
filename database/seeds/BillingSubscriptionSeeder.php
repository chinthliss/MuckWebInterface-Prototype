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

        //Valid Owned New Subscription
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

        //Valid Owned Active Subscription
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
