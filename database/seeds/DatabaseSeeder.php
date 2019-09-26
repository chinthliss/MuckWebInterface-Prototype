<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for tests.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        //Account 1 - Validated user account
        DB::table('accounts')->insert([
            'aid' => 1,
            'uuid' => '11111111',
            'email' => 'test@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);
        $aid = DB::table('accounts')->where('email', 'test@test.com')->first()->aid;

        DB::table('account_emails')->insert([
            'aid' => $aid,
            'email' => 'test@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_emails')->insert([
            'aid' => $aid,
            'email' => 'testalt@test.com',
            'verified_at' => Carbon::now()
        ]);

        // Account 2 - unverified
        DB::table('accounts')->insert([
            'aid' => 2,
            'uuid' => '22222222',
            'email' => 'testunverified@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        $aid = DB::table('accounts')->where('email', 'testunverified@test.com')->first()->aid;

        DB::table('account_emails')->insert([
            'aid' => $aid,
            'email' => 'testunverified@test.com'
        ]);

        // Account 3 - unverified and account_emails record is missing
        DB::table('accounts')->insert([
            'aid' => 3,
            'uuid' => '33333333',
            'email' => 'testbrokenunverified@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

    }
}
