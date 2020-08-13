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
            'amount_usd' => 10.00
        ]);

        DB::table('billing_itemcatalogue')->insert([
            'code' => 'TESTITEM2',
            'name' => 'Another Item',
            'description' => "This is an addition item with a different description to test this functionality.",
            'amount_usd' => 10.00
        ]);

    }
}
