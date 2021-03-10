<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AccountAdminSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     *
     * @return void
     */
    public function run()
    {

        DB::table('account_notes')->insert([
            'aid' => 1,
            'when' => Carbon::now()->timestamp,
            'message' => 'Test Note',
            'staff_member' => 'test_member',
            'game' => config('muck.muck_name')
        ]);

    }
}
