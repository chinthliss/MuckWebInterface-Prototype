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

        // ****************************************
        //Account 1 - Validated user account that's accepted TOS
        $validAid = 1;
        DB::table('accounts')->insert([
            'aid' => $validAid,
            'uuid' => '11111111',
            'email' => 'test@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

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


        // ****************************************
        // Account 2 - unverified
        $unverifiedAid = 2;
        DB::table('accounts')->insert([
            'aid' => $unverifiedAid,
            'uuid' => '22222222',
            'email' => 'testunverified@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $unverifiedAid,
            'email' => 'testunverified@test.com'
        ]);


        // ****************************************
        // Account 3 - unverified and account_emails record is missing
        $unverifiedAndNoEmailRecordAid = 3;
        DB::table('accounts')->insert([
            'aid' => $unverifiedAndNoEmailRecordAid,
            'uuid' => '33333333',
            'email' => 'testbrokenunverified@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);


        // ****************************************
        // Acount 4 - verified but hasn't accepted TOS
        $notAgreedToTosAid = 4;
        DB::table('accounts')->insert([
            'aid' => $notAgreedToTosAid,
            'uuid' => '44444444',
            'email' => 'notagreedtotos@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

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


        // ****************************************
        // Account 5 - Second validated user, with the role of other_role
        $validWithOtherRoleAid = 5;
        DB::table('accounts')->insert([
            'aid' => $validWithOtherRoleAid,
            'uuid' => '55555555',
            'email' => 'secondvalid@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_roles')->insert([
            'aid' => $validWithOtherRoleAid,
            'roles' => 'other_role'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $validWithOtherRoleAid,
            'email' => 'secondvalid@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => $validWithOtherRoleAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);


        // ****************************************
        // Account 6 - Staff account
        $staffAccountAid = 6;
        DB::table('accounts')->insert([
            'aid' => $staffAccountAid,
            'uuid' => '66666666',
            'email' => 'staff@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $staffAccountAid,
            'email' => 'staff@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_roles')->insert([
            'aid' => $staffAccountAid,
            'roles' => 'staff'
        ]);

        DB::table('account_properties')->insert([
            'aid' => $staffAccountAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);


        // ****************************************
        // Account 7 - Admin account
        $adminAccountAid = 7;
        DB::table('accounts')->insert([
            'aid' => $adminAccountAid,
            'uuid' => '7777777',
            'email' => 'admin@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT'
        ]);

        DB::table('account_emails')->insert([
            'aid' => $adminAccountAid,
            'email' => 'admin@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_roles')->insert([
            'aid' => $adminAccountAid,
            'roles' => 'admin'
        ]);

        DB::table('account_properties')->insert([
            'aid' => $adminAccountAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);


        // ****************************************
        // Account 8 - banned Account
        $bannedAccountAid = 8;
        DB::table('accounts')->insert([
            'aid' => $bannedAccountAid,
            'uuid' => '88888888',
            'email' => 'banned@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589', //password
            'password_type' => 'SHA1SALT',
            'locked_at' => Carbon::now()
        ]);

        DB::table('account_emails')->insert([
            'aid' => $bannedAccountAid,
            'email' => 'banned@test.com',
            'verified_at' => Carbon::now()
        ]);

        DB::table('account_properties')->insert([
            'aid' => $bannedAccountAid,
            'propname' => 'tos-hash-viewed',
            'proptype' => 'STRING',
            'propdata' => TermsOfService::getTermsOfServiceHash()
        ]);


        //Remember to do 'composer dump-autoload' before adding to this
        $this->call([
            BillingItemCatalogueSeeder::class
        ]);
    }
}
