<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testCheckSeedIsOkay()
    {
        $this->assertDatabaseHas('accounts', [
            'aid' => 1,
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testalt@test.com'
        ]);
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testChangeEmailRequiresLogin()
    {
        $response = $this->followingRedirects()->get('account/changeemail');
        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    /**
     * @depends testChangeEmailRequiresLogin
     */
    public function testChangeEmailAccessibleWithLogin()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->get('account/changeemail');
        $response->assertSuccessful();
        $response->assertViewIs('auth.email-change');
    }

    /**
     * @depends testChangeEmailAccessibleWithLogin
     */
    public function testChangeEmailRequiresValidExistingPassword()
    {
        $this->loginAsValidatedUser();
        $response = $this->JSON('POST', 'account/changeemail', [
            'password' => 'wrongpassword',
            'email' => 'testnew@test.com'
        ]);
        $response->assertStatus(422);
    }

    /**
     * @depends testChangeEmailAccessibleWithLogin
     */
    public function testChangeEmailRequiresUnusedEmail()
    {
        $this->loginAsValidatedUser();
        $response = $this->JSON('POST', 'account/changeemail', [
            'password' => 'password',
            'email' => 'testalt@test.com'
        ]);
        $response->assertStatus(422);
    }

    /**
     * @depends testChangeEmailAccessibleWithLogin
     */
    public function testChangeEmailSendsVerification()
    {
        Notification::fake();
        Notification::assertNothingSent();
        $user = $this->loginAsValidatedUser();
        $this->post('account/changeemail', [
            'password' => 'password',
            'email' => 'testnew@test.com'
        ]);
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertRedirect();
            return true;
        });
    }

    /**
     * @depends testChangeEmailSendsVerification
     */
    public function testChangeEmailWorks()
    {
        Notification::fake();
        $user = $this->loginAsValidatedUser();
        $newEmail = 'testnew@test.com';
        $this->post('account/changeemail', [
            'password' => 'password',
            'email' => $newEmail
        ]);
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user, $newEmail) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertRedirect();
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmailForVerification(), $newEmail, "Email didn't change.");
            $this->assertDatabaseHas('account_emails', [
                'email' => $newEmail
            ]);
            return true;
        });
    }

    #region Use Existing Email
    public function testUseExistingEmailWorksWithVerifiedMail()
    {
        Notification::fake();
        $user = $this->loginAsValidatedUser();
        $newEmail = 'testalt@test.com';
        $this->post('account/useexistingemail', [
            'email' => $newEmail
        ]);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEquals($user->getEmailForVerification(), $newEmail, "Email didn't change.");
        Notification::assertNothingSent();
    }

    public function testUseExistingEmailWorksWithUnverifiedMail()
    {
        Notification::fake();
        $user = $this->loginAsValidatedUser();
        $newEmail = 'testaltunverified@test.com';
        $this->post('account/useexistingemail', [
            'email' => $newEmail
        ]);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertEquals($user->getEmailForVerification(), $newEmail, "Email wasn't changed.");
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, $channels) use ($user, $newEmail) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertRedirect();
            $this->assertTrue($user->hasVerifiedEmail());
            return true;
        });
    }

    public function testUseExistingEmailRequiresExistingEmail()
    {
        Notification::fake();
        $user = $this->loginAsValidatedUser();
        $newEmail = 'notexistingemail@test.com';
        $this->post('account/useexistingemail', [
            'email' => $newEmail
        ]);
        $this->assertNotEquals($user->getEmailForVerification(), $newEmail, "Email changed to new email.");
        Notification::assertNothingSent();
    }
    #endregion Use Existing Email

    public function testFindByAnyEmailReturnsAlternativeEmail()
    {
        $user = User::findByEmail('testalt@test.com', true);
        $this->assertNotNull($user);
    }

    public function testFindByPrimaryEmailDoesNotReturnAlternativeEmail()
    {
        $user = User::findByEmail('testalt@test.com', false);
        $this->assertNull($user);
    }

}

