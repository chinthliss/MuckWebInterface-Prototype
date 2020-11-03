<?php

namespace Tests\Feature;

use Auth;
use App\Helpers\MuckInterop;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
        $this->assertDatabaseHas('accounts', [
            'email' => 'test@test.com'
        ]);
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testForgottenPasswordRequestWorks()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'test@test.com'
        ]));
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-reset-sent');
    }

    /**
     * @depends testForgottenPasswordRequestWorks
     */
    public function testInvalidEmailWorks()
    {
        $this->seed();
        $response = $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'invalidemail@test.com'
        ]));
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-reset-sent');
    }

    public function testForgottenPasswordResetCannotBeAccessedDirectly()
    {
        $response = $this->followingRedirects()->get('/account/passwordreset');
        $response->assertStatus(404);
    }

    /**
     * @depends testForgottenPasswordRequestWorks
     */
    public function testForgottenPasswordRequestRequiresEmail()
    {
        $response = $this->json('POST', route('auth.account.passwordforgotten', []));
        $response->assertStatus(422);
    }

    /**
     * @depends testForgottenPasswordRequestWorks
     */
    public function testForgottenPasswordRequestIsThrottled()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->json('POST', route('auth.account.passwordforgotten', []));
        }
        $response->assertStatus(429);
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testResetPasswordEmailSentAfterForgottenPasswordRequest()
    {
        $this->seed();
        Notification::fake();
        Notification::assertNothingSent();
        $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'test@test.com'
        ]));
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
        Notification::assertSentTo([$user], ResetPassword::class);
        Notification::assertTimesSent(1, ResetPassword::class);
    }

    /**
     * @depends testResetPasswordEmailSentAfterForgottenPasswordRequest
     */
    public function testResetPasswordEmailHasLink()
    {
        $this->seed();
        Notification::fake();
        $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'test@test.com'
        ]));
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
        Notification::assertSentTo($user,ResetPassword::class, function(ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $this->assertStringContainsStringIgnoringCase('signature=', $mail['actionUrl']);
            return true;
        });

    }

    /**
     * @depends testResetPasswordEmailHasLink
     */
    public function testResetLinkProvidesAccessToPasswordReset()
    {
        $this->seed();
        Notification::fake();
        $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'test@test.com'
        ]));
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
        Notification::assertSentTo($user,ResetPassword::class, function(ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('GET', $mail['actionUrl']);
            $response->assertSuccessful();
            return true;
        });
    }

    /**
     * @depends testResetLinkProvidesAccessToPasswordReset
     */
    public function testResetPasswordWorks()
    {
        $this->seed();
        Notification::fake();
        $this->json('POST', route('auth.account.passwordforgotten', [
            'email' => 'test@test.com'
        ]));
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
        Notification::assertSentTo($user,ResetPassword::class, function(ResetPassword $notification, $channels) use ($user) {
            $mail = $notification->toMail($user)->toArray();
            $response = $this->json('POST', $mail['actionUrl'],
                ['password'=>'passwordchanged', 'password_confirmation'=>'passwordchanged']);
            $response->assertSuccessful();
            //Need to re-fetch password
            $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
            $this->assertTrue(auth()->guard()->getProvider()->validateCredentials($user, ['password'=>'passwordchanged']));
            $response->assertSuccessful();
            return true;
        });
    }


}

