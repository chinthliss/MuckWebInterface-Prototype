<?php

namespace Tests\Feature;

use Auth;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function testCheckSeedIsOkay()
    {
        $this->seed();
        $this->assertDatabaseHas('accounts', [
            'aid' => 1,
            'email' => 'test@test.com'
        ]);
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testChangePasswordRequiresLogin()
    {
        $response = $this->followingRedirects()->get('account/changepassword');
        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    /**
     * @depends testChangePasswordRequiresLogin
     */
    public function testChangePasswordAccessibleWithLogin()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->get('account/changepassword');
        $response->assertSuccessful();
        $response->assertViewIs('auth.password-change');
    }

    /**
     * @depends testChangePasswordAccessibleWithLogin
     */
    public function testNewPasswordMustNotEqualOldPassword()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->JSON('POST', 'account/changepassword', [
            'oldpassword' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $response->assertStatus(422);
    }

    /**
     * @depends testChangePasswordAccessibleWithLogin
     */
    public function testChangePasswordRequiresValidExistingPassword()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->JSON('POST', 'account/changepassword', [
            'oldpassword' => 'wrongpassword',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        $response->assertStatus(422);
    }

    /**
     * @depends testChangePasswordAccessibleWithLogin
     */
    public function testChangePasswordWorks()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->post('account/changepassword', [
            'oldpassword' => 'password',
            'password' => 'passwordchanged',
            'password_confirmation' => 'passwordchanged'
        ]);
        $response->assertSuccessful();
        $user = auth()->guard()->getProvider()->retrieveByCredentials(['email'=>'test@test.com']);
        $this->assertFalse(auth()->guard()->getProvider()->validateCredentials($user, ['password'=>'password']),
            "Password hasn't changed.");
    }

}

