<?php

use Illuminate\Database\Seeder;

class BillingItemCatalogueSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     *
     * @return void
     */
    public function run()
    {
        DB::table('billing_itemcatalogue')->insert([
            'code' => 'TESTITEM',
            'name' => 'Test Item',
            'description' => "This is a test item.",
            'amount_usd' => 5.00
        ]);

        DB::table('billing_itemcatalogue')->insert([
            'code' => 'TESTITEM2',
            'name' => 'Another Item',
            'description' => "This is an addition item with a different description to test this functionality.",
            'amount_usd' => 10.00
        ]);

        DB::table('billing_itemcatalogue')->insert([
            'code' => 'TESTITEM3',
            'name' => 'A supporter item',
            'description' => "This particular test item gives supporter points!",
            'amount_usd' => 15.00,
            'supporter' => true
        ]);
    }
}
