<?php

namespace Tests\Feature;

use App\Helpers\MuckInterop;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User as User;

class AccountCreateTest extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
        //TODO: Find way to test the test password!
        $this->assertDatabaseHas('accounts', [
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('account_emails', [
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testalt@test.com'
        ]);
    }

    public function testNewPasswordCanBeCheckedAgainst()
    {
        $password = MuckInterop::createSHA1SALTPassword('password');
        $this->assertTrue(MuckInterop::verifySHA1SALTPassword('password', $password));
    }

    public function testCanCreateAccountWithValidCredentials()
    {
        $this->expectsEvents(Registered::class);
        $response = $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $response->assertSuccessful();
        $response->assertJsonStructure(['redirectUrl']);
        $this->assertAuthenticated();
    }

    /**
     * @depends testCanCreateAccountWithValidCredentials
     */
    public function testCreatedAccountAlsoRecordsInAccountEmailsTable()
    {
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testnew@test.com'
        ]);
    }

    public function testCannotCreateAccountWithoutEmail()
    {
        $response = $this->json('POST', route('auth.account.create', [
            'password' => 'password'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function testCannotCreateAccountWithoutPassword()
    {
        $response = $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function testCannotCreateAccountWithNonAnsiInPassword()
    {
        $response = $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'パスワード' //Google-translated 'password'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCannotCreateAccountWithExistingEmail()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.create', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCannotCreateAccountWithExistingAlternativeEmail()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.create', [
            'email' => 'testalt@test.com',
            'password' => 'password'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }


    /**
     * @depends testCheckSeedIsOkay
     */
    public function testVerifyEmailSentAfterAccountCreation()
    {
        Notification::fake();
        Notification::assertNothingSent();
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        Notification::assertSentTo([$user], VerifyEmail::class);
        Notification::assertTimesSent(1, VerifyEmail::class);
    }

    /**
     * @depends testCanCreateAccountWithValidCredentials
     */
    public function testCreatedAccountHasRememberTokenSet()
    {
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $this->assertNotEmpty($user->getRememberToken());
    }

    /**
     * @depends testCanCreateAccountWithValidCredentials
     */
    public function testNewAccountDoesNotPopulateRememberTokenIfRequested()
    {
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password',
            'forget' => true
        ]));
        $user = auth()->user();
        $this->assertEmpty($user->getRememberToken());
    }

    /**
     * @depends testCanCreateAccountWithValidCredentials
     */
    public function testNewAccountHasTimeStamps()
    {
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password',
            'forget' => true
        ]));
        $user = auth()->user();
        $this->assertNotNull($user->createdAt);
        $this->assertNotNull($user->updatedAt);
    }

    public function testNewReferredAccountHasReferralSet()
    {
        $this->seed();

        //Check initial visit sets referral on the session
        $request = $this->get('/?refer=1');
        $request->assertSessionHas('account.referral', 1);

        // Create account and ensures such is saved
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $referredBy = $user->getAccountProperty('tutor');
        $this->assertEquals(1, $referredBy, "Referral account wasn't set.");

        //Check account's referral count is correct
        $referrer = User::find(1);
        $this->assertEquals(1, $referrer->getReferralCount());
    }
}

