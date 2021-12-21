<?php

namespace Tests\Feature;

use AccountAdminSeeder;
use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountAdminTest extends TestCase
{
    use RefreshDatabase;

    public function testGetAccountNotesReturnsNotes()
    {
        $this->seed();
        $this->seed(AccountAdminSeeder::class);
        $user = User::find(1);
        $this->assertNotNull($user, 'Expected test user did not exist');
        $notes = $user->getAccountNotes();
        $this->assertNotEmpty($notes);
    }

    public function testSiteAdminCanOpenAdminAccountPage()
    {
        $this->seed();
        $this->loginAsSiteAdminUser();
        $response = $this->followingRedirects()->get(route('admin.account', [
            'accountId' => 1
        ]));
        $response->assertSuccessful();
    }

    public function testNonAdminCanNotOpenAdminAccountPage()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->get(route('admin.account', [
            'accountId' => 1
        ]));
        $response->assertForbidden();
    }
}

