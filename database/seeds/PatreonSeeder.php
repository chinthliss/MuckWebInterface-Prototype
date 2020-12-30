<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PatreonSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     *
     * @return void
     */
    public function run()
    {

        DB::table('patreon_users')->insert([
            'patron_id' => 1,
            'email' => 'test@test.com',
            'full_name' => 'TestFullName',
            'vanity' => 'TestVanity',
            'hide_pledges' => false,
            'url' => 'https://www.patreon.com/FakeUser',
            'thumb_url' => 'https://fakepatreonusercontent.com.fake'
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

        DB::table('patreon_claims')->insert(
            [
                'campaign_id' => 1,
                'patron_id' => 1,
                'claimed_cents' => 50
            ]
        );
    }
}
