<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Payment\PaymentTransactionItem;

class BillingTransactionsSeeder extends Seeder
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

        // Payment tests
        DB::table('billing_profiles')->insert([
            'aid' => $validAid,
            'profileid' => 1,
            'defaultcard' => 1,
            'spendinglimit' => 0
        ]);

        DB::table('billing_paymentprofiles')->insert([
            'id' => 1,
            'profileid' => $validAid,
            'paymentid' => 1,
            'cardtype' => 'MasterCard',
            'maskedcardnum' => 4444,
            'expdate' => '10/2021',
            'firstname' => '',
            'lastname' => ''
        ]);

        //Completed card payment
        DB::table('billing_transactions')->insert([
            'id' => '00000000-0000-0000-0000-000000000001',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'accountcurrency_rewarded' => 30,
            'purchase_description' => '30 bananas',
            'created_at' => Carbon::now(),
            'completed_at' => Carbon::now(),
            'result' => 'fulfilled'
        ]);

        //Pending card payment
        DB::table('billing_transactions')->insert([
            'id' => '00000000-0000-0000-0000-000000000002',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'purchase_description' => '30 bananas',
            'created_at' => Carbon::now()
        ]);

        //Another user's payment
        DB::table('billing_transactions')->insert([
            'id' => '00000000-0000-0000-0000-000000000003',
            'account_id' => $secondAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 2,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'purchase_description' => '30 bananas',
            'created_at' => Carbon::now()
        ]);

        //Pending card payment with an item
        $item = new PaymentTransactionItem(
            'TESTITEM',
            'Test Item',
            1,
            10.0,
            30
        );
        DB::table('billing_transactions')->insert([
            'id' => '00000000-0000-0000-0000-000000000004',
            'account_id' => $validAid,
            'vendor' => 'authorizenet',
            'vendor_profile_id' => 1,
            'amount_usd' => 10,
            'amount_usd_items' => 0,
            'accountcurrency_quoted' => 30,
            'purchase_description' => '30 bananas',
            'created_at' => Carbon::now(),
            'items_json' => json_encode([$item->toArray()])
        ]);
    }
}
