<?php

namespace Tests\Feature;

use Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class AccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
        $this->assertDatabaseHas('account_emails', [
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testunverified@test.com'
        ]);
    }

    public function testCannotAccessVerificationPageWithoutAccount()
    {
        $response = $this->followingRedirects()->get('/account/verifyemail');
        $response->assertViewIs('auth.login');
    }

    public function testVerifyEmailHasLink()
    {
        Notification::fake();
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    public function testExistingVerifiedEmailIsVerified()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $response->assertStatus(200);
        $this->assertAuthenticated();
        $user = $this->getPresentUser();
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function testExistingUnverifiedEmailIsUnverified()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'testunverified@test.com',
            'password' => 'password'
        ]));
        $response->assertStatus(200);
        $this->assertAuthenticated();
        $user = $this->getPresentUser();
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function testNewUserIsNotVerified()
    {
        Notification::fake();
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = $this->getPresentUser();
        $this->assertNotTrue($user->hasVerifiedEmail());
        $request = $this->followingRedirects()->get('/');
        $request->assertViewIs('auth.verify');
    }

    /**
     * @depends testVerifyEmailHasLink
     */
    public function testNewUserIsVerifiedAfterClickingLink()
    {
        Notification::fake();
        $this->json('POST', route('auth.account.create', [
            'email' => 'testnew@test.com',
            'password' => 'password'
        ]));
        $user = $this->getPresentUser();
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertStatus(302);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmailForVerification(), 'testnew@test.com');
            return true;
        });
    }

    public function testVerificationLinkCanBeResent()
    {
        $this->seed();
        Notification::fake();
        Auth::loginUsingId(2);
        $user = $this->getPresentUser();
        $this->get('/account/resendverifyemail');
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });
    }

    /**
     * @depends testNewUserIsVerifiedAfterClickingLink
     */
    public function testVerificationWorksOnAccountWithoutEmailRecord()
    {
        $this->seed();
        Notification::fake();
        Auth::loginUsingId(3);
        $user = $this->getPresentUser();
        $this->get('/account/resendverifyemail');
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertStatus(302);
            $this->assertDatabaseHas('account_emails', [
                'email' => 'testbrokenunverified@test.com'
            ]);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmailForVerification(), 'testbrokenunverified@test.com');
            return true;
        });
    }

}
