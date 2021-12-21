<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountRolesTest extends TestCase
{
    use RefreshDatabase;

    private $staffRequiredPage = '/support/agent';
    private $adminRequiredPage = '/admin/accounts';
    private $siteAdminRequiredPage = '/accountcurrency/transactions';
    private $staffCharacter = 2345;
    private $adminCharacter = 6789;

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

    public function testUserDoesNotHaveSiteAdminRole()
    {
        $user = $this->loginAsValidatedUser();
        $this->assertFalse($user->hasRole('siteadmin'));
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

    public function testStaffDoesNotHaveSiteAdminRole()
    {
        $user = $this->loginAsStaffUser();
        $this->assertFalse($user->hasRole('siteadmin'));
    }

    public function testAdminHasAnyRole()
    {
        $user = $this->loginAsSiteAdminUser();
        $this->assertTrue($user->hasRole('staff'));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('siteadmin'));
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

    public function testUserCannotAccessSiteAdminPage()
    {
        $this->loginAsValidatedUser();
        $response = $this->get($this->siteAdminRequiredPage);
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

    public function testStaffCanNotAccessSiteAdminPage()
    {
        $this->loginAsStaffUser();
        $response = $this->get($this->siteAdminRequiredPage);
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

    public function testAdminCannotAccessSiteAdminPage()
    {
        $this->loginAsAdminUser();
        $response = $this->get($this->siteAdminRequiredPage);
        $response->assertForbidden();
    }

    public function testStaffCharacterCanAccessStaffPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->staffCharacter]);
        $response = $this->get($this->staffRequiredPage);
        $response->assertSuccessful();
    }

    public function testStaffCharacterCannotAccessAdminPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->staffCharacter]);
        $response = $this->get($this->adminRequiredPage);
        $response->assertForbidden();
    }

    public function testStaffCharacterCannotAccessSiteAdminPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->staffCharacter]);
        $response = $this->get($this->siteAdminRequiredPage);
        $response->assertForbidden();
    }

    public function testAdminCharacterCanAccessStaffPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->adminCharacter]);
        $response = $this->get($this->staffRequiredPage);
        $response->assertSuccessful();
    }

    public function testAdminCharacterCanAccessAdminPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->adminCharacter]);
        $response = $this->get($this->adminRequiredPage);
        $response->assertSuccessful();
    }

    public function testAdminCharacterCanNotAccessSiteAdminPage()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => $this->adminCharacter]);
        $response = $this->get($this->siteAdminRequiredPage);
        $response->assertForbidden();
    }
    #endregion
}

