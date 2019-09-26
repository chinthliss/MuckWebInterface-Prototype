<?php

namespace Tests\Feature;

use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
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
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * @depends testChangeEmailRequiresLogin
     */
    public function testChangeEmailAccessibleWithLogin()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->get('account/changeemail');
        $response->assertStatus(200);
        $response->assertViewIs('auth.email-change');
    }

    /**
     * @depends testChangeEmailAccessibleWithLogin
     */
    public function testChangeEmailRequiresValidExistingPassword()
    {
        $this->seed();
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
        $this->seed();
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
        $this->seed();
        Notification::fake();
        Notification::assertNothingSent();
        $user = $this->loginAsValidatedUser();
        $response = $this->post('account/changeemail', [
            'password' => 'password',
            'email' => 'testnew@test.com'
        ]);
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertStatus(302);
            return true;
        });
    }

    /**
     * @depends testChangeEmailSendsVerification
     */
    public function testChangeEmailWorks()
    {
        $this->seed();
        Notification::fake();
        $user = $this->loginAsValidatedUser();
        $newEmail = 'testnew@test.com';
        $this->post('account/changeemail', [
            'password' => 'password',
            'email' => $newEmail
        ]);
        Notification::assertSentTo($user,VerifyEmail::class, function(VerifyEmail $notification, $channels) use ($user, $newEmail) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertStatus(302);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertEquals($user->getEmailForVerification(), $newEmail, "Email didn't change.");
            $this->assertDatabaseHas('account_emails', [
                'email' => $newEmail
            ]);
            return true;
        });
    }

}

