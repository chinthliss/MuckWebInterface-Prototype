<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountRolesTest extends TestCase
{
    use RefreshDatabase;

    private $staffId = 6;
    private $adminId = 7;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testCheckSeedIsOkay()
    {
        $this->assertDatabaseHas('accounts', [
            'email' => 'test@test.com',
            'password' => '0A095F587AFCB082:EC2F0D2ACB7788E26E0A36C32C6475C589860589' // Password
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
        $user = $this->loginAsValidatedUser();
        $this->assertFalse($user->hasRole('admin'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testAdminDoesHaveAdminRole()
    {
        $user = $this->loginAsAdminUser();
        $this->asserttrue($user->hasRole('admin'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testAdminHasAnyRole()
    {
        $user = $this->loginAsAdminUser();
        $this->assertTrue($user->hasRole('other_role'));
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testUserWithRoleHasThatRole()
    {
        Auth::loginUsingId($this->adminId);
        $user = $this->getPresentUser();
        $this->assertTrue($user->hasRole('other_role'));
    }

    public function testUserCannotAccessAdminPage()
    {
        $this->loginAsValidatedUser();
        $response = $this->get('/admin');
        $response->assertForbidden();
    }

    public function testAdminCanAccessAdminPage()
    {
        Auth::loginUsingId($this->adminId);
        $response = $this->get('/admin');
        $response->assertSuccessful();
    }

    public function testStaffCanNotAccessAdminPage()
    {
        Auth::loginUsingId($this->staffId);
        $response = $this->get('/accountcurrency/transactions');
        $response->assertForbidden();
    }
}

