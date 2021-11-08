<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountRolesTest extends TestCase
{
    use RefreshDatabase;

    private $staffRequiredPage = '/admin';
    private $adminRequiredPage = '/accountcurrency/transactions';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #region hasRole testing

    public function testUserDoesNotHaveStaffRole()
    {
        $user = $this->loginAsValidatedUser();
        $this->assertFalse($user->hasRole('staff'));
    }

    public function testUserDoesNotHaveAdminRole()
    {
        $user = $this->loginAsValidatedUser();
        $this->assertFalse($user->hasRole('admin'));
    }

    public function testStaffDoesHaveStaffRole()
    {
        $user = $this->loginAsStaffUser();
        $this->assertTrue($user->hasRole('staff'));
    }

    public function testStaffDoesNotHaveAdminRole()
    {
        $user = $this->loginAsStaffUser();
        $this->assertFalse($user->hasRole('admin'));
    }

    public function testAdminHasAnyRole()
    {
        $user = $this->loginAsAdminUser();
        $this->assertTrue($user->hasRole('staff'));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('not_actually_a_role'));
    }

    #endregion

    #region Middleware testing
    public function testUserCannotAccessStaffPage()
    {
        $this->loginAsValidatedUser();
        $response = $this->get($this->staffRequiredPage);
        $response->assertForbidden();
    }

    public function testUserCannotAccessAdminPage()
    {
        $this->loginAsValidatedUser();
        $response = $this->get($this->adminRequiredPage);
        $response->assertForbidden();
    }

    public function testStaffCanAccessStaffPage()
    {
        $this->loginAsStaffUser();
        $response = $this->get($this->staffRequiredPage);
        $response->assertSuccessful();
    }

    public function testStaffCanNotAccessAdminPage()
    {
        $this->loginAsStaffUser();
        $response = $this->get($this->adminRequiredPage);
        $response->assertForbidden();
    }

    public function testAdminCanAccessStaffPage()
    {
        $this->loginAsAdminUser();
        $response = $this->get($this->staffRequiredPage);
        $response->assertSuccessful();
    }

    public function testAdminCanAccessAdminPage()
    {
        $this->loginAsAdminUser();
        $response = $this->get($this->adminRequiredPage);
        $response->assertSuccessful();
    }

    public function testStaffCharacterCanAccessStaffPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $response = $this->get($this->staffRequiredPage);
        $response->assertSuccessful();
    }

    public function testStaffCharacterCannotAccessAdminPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $response = $this->get($this->adminRequiredPage);
        $response->assertForbidden();
    }

    #endregion
}

