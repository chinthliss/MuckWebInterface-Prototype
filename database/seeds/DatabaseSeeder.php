<?php

use App\TermsOfService;
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

        //Account 1 - Validated user account that's accepted TOS
        DB::table('accounts')->insert([
            'aid' => 1,
            'uuid' => '11111111',
            'email' => 'test@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);
        $validAid = DB::table('accounts')->where('email', 'test@test.com')->first()->aid;

        DB::table('account_emails')->insert([
            'aid' => $validAid,
            'email' => 'test@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_emails')->insert([
            'aid' => $validAid,
            'email' => 'testalt@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_emails')->insert([
            'aid' => $validAid,
            'email' => 'testaltunverified@test.com'
        ]);

        DB::table('account_properties')->insert([
            'aid' => $validAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);

        // Account 2 - unverified
        DB::table('accounts')->insert([
            'aid' => 2,
            'uuid' => '22222222',
            'email' => 'testunverified@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        $unverifiedAid = DB::table('accounts')->where('email', 'testunverified@test.com')->first()->aid;

        DB::table('account_emails')->insert([
            'aid' => $unverifiedAid,
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

        //Acount 4 - verified but hasn't accepted TOS
        DB::table('accounts')->insert([
            'aid' => 4,
            'uuid' => '44444444',
            'email' => 'notagreedtotos@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);
        $notAgreedToTosAid = DB::table('accounts')->where('email', 'notagreedtotos@test.com')->first()->aid;

        DB::table('account_emails')->insert([
            'aid' => $notAgreedToTosAid,
            'email' => 'notagreedtotos@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => $notAgreedToTosAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => 'OldHash'
        ]);

        //Account 5 - Second validated user, with the role of other_role
        DB::table('accounts')->insert([
            'aid' => 5,
            'uuid' => '55555555',
            'email' => 'secondvalid@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_roles')->insert([
            'aid' => 5,
            'roles' => 'other_role'
        ]);

        DB::table('account_emails')->insert([
            'aid' => 5,
            'email' => 'secondvalid@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => 5,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);

        //Account 6 - Admin account
        DB::table('accounts')->insert([
            'aid' => 6,
            'uuid' => '66666666',
            'email' => 'admin@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => 6,
            'email' => 'admin@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_roles')->insert([
            'aid' => 6,
            'roles' => 'admin'
        ]);

        //Remember to do 'composer dump-autoload' before adding to this
        $this->call([
            BillingItemCatalogueSeeder::class
        ]);
    }
}
