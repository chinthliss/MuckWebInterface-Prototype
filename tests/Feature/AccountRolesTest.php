<?php

namespace Tests\Feature;

use App\Helpers\MuckInterop;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountRolesTest extends TestCase
{
    use RefreshDatabase;

    private $adminId = 5;

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
            'email' => 'admin@test.com'
        ]);
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testUserDoesNotHaveAdminRole()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $this->assertFalse($user->hasRole('admin'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testAdminDoesHaveAdminRole()
    {
        $this->seed();
        $user = $this->loginAsAdminUser();
        $this->asserttrue($user->hasRole('admin'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testAdminHasAnyRole()
    {
        $this->seed();
        $user = $this->loginAsAdminUser();
        $this->assertTrue($user->hasRole('other_role'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testUserWithRoleHasThatRole()
    {
        $this->seed();
        Auth::loginUsingId($this->adminId);
        $user = $this->getPresentUser();
        $this->assertTrue($user->hasRole('other_role'));
    }

    public function testUserCannotAccessAdminPage()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->get('/admin');
        $response->assertForbidden();
    }

    public function testAdminCanAccessAdminPage()
    {
        $this->seed();
        Auth::loginUsingId($this->adminId);
        $response = $this->get('/admin');
        $response->assertRedirect();
    }
}

