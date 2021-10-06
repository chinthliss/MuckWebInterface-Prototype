<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MuckObjectSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     * The seeds in here tie in with the fakes returned via the MuckConnection faker.
     *
     * @return void
     */
    public function run()
    {
        $fixedTime = Carbon::create(2000,1,1, 0, 0, 0 );

        DB::table('muck_objects')->insert([
            'id' => 1,
            'game_code' => config('muck.muck_code'),
            'dbref' => 1234,
            'created_at' => $fixedTime,
            'type' => 'player',
            'name' => 'TestCharacter'
        ]);

        // Active Membership
        DB::table('patreon_members')->insert([
            'campaign_id' => 1,
            'patron_id' => 1,
            'currently_entitled_amount_cents' => 500,
            'is_follower' => true,
            'last_charge_status' => 'Paid',
            'last_charge_date' => Carbon::now(),
            'lifetime_support_cents' => 1000,
            'patron_status' => 'active_patron',
            'pledge_relationship_start' => Carbon::now()
        ]);

        //Legacy claims
        DB::table('patreon_claims')->insert([
            'campaign_id' => 1,
            'patron_id' => 1,
            'claimed_cents' => 250
        ]);

        //Existing claims
        DB::table('billing_transactions')->insert([
            'id' => 'PatreonSeederTransaction1',
            'account_id' => 1,
            'vendor' => 'patreon',
            'vendor_profile_id' => '1',
            'subscription_id' => '1',
            'amount_usd' => 1.00,
            'amount_usd_items' => 0.00,
            'accountcurrency_quoted' => 2,
            'accountcurrency_rewarded' => 2,
            'purchase_description'=>'test',
            'paid_at' => Carbon::now()
        ]);

        DB::table('billing_transactions')->insert([
            'id' => 'PatreonSeederTransaction2',
            'account_id' => 1,
            'vendor' => 'patreon',
            'vendor_profile_id' => '1',
            'subscription_id' => '1',
            'amount_usd' => 0.50,
            'amount_usd_items' => 0.00,
            'accountcurrency_quoted' => 1,
            'accountcurrency_rewarded' => 1,
            'purchase_description'=>'test',
            'paid_at' => Carbon::now()
        ]);
    }
}
