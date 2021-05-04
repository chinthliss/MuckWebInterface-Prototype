<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testCheckSeedIsOkay()
    {
        //TODO: Find way to test the test password!
        $this->assertDatabaseHas('accounts', [
            'email' => 'test@test.com'
        ]);
        $this->assertDatabaseHas('account_emails', [
            'email' => 'testalt@test.com'
        ]);
    }

    public function testCanAccessLoginPage()
    {
        $response = $this->get('/login');
        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCanLoginWithCorrectCredentials()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $response->assertSuccessful();
        $this->assertAuthenticated();
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCannotLoginWithAlternativeEmail()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'testalt@test.com',
            'password' => 'password'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCanLoginWithCorrectMuckCredentials()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'testCharacter',
            'password' => 'password'
        ]));
        $response->assertSuccessful();
        $this->assertAuthenticated();
        $this->assertNotNull($this->getPresentUser()->getCharacter(), "Character should be set after logging in with such.");
        $this->get('/home'); //Make sure it stays set
        $this->assertNotNull($this->getPresentUser()->getCharacter(), "Character didn't remain set.");
    }

    /**
     * @depends testCanLoginWithCorrectMuckCredentials
     */
    public function testCannotLoginWithIncorrectMuckCredentials()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'testCharacter',
            'password' => 'wrongPassword'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    /**
     * @depends testCanLoginWithCorrectCredentials
     */
    public function testCannotAccessLoginPageWhenLoggedIn()
    {
        $this->loginAsValidatedUser();
        $this->assertAuthenticated();
        $response = $this->get('/login');
        $response->assertRedirect('/home');
    }

    public function testCannotAccessHomeWhenNotLoggedIn()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testCannotLoginWithIncorrectCredentials()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'wrong'
        ]));
        $response->assertStatus(422);
        $this->assertGuest();
    }

    /**
     * @depends testCheckSeedIsOkay
     */
    public function testLoginResponseContainsRedirectUrl()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $response->assertJsonStructure(['redirectUrl']);
    }

    /**
     * @depends testCanLoginWithCorrectCredentials
     */
    public function testCanLogout()
    {
        $this->loginAsValidatedUser();
        $this->assertAuthenticated();
        $response = $this->post(route('logout'));
        $response->assertRedirect();
        $this->assertGuest();
    }

    /**
     * @depends testCanLoginWithCorrectCredentials
     */
    public function testLoginPopulatesRememberToken()
    {
        $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $this->assertNotEmpty($user->getRememberToken());
    }

    /**
     * @depends testCanLoginWithCorrectCredentials
     */
    public function testLoginDoesNotPopulateRememberTokenIfRequested()
    {
        $this->json('POST', route('auth.account.login', [
            'email' => 'test@test.com',
            'password' => 'password',
            'forget' => true
        ]));
        $user = auth()->user();
        $this->assertEmpty($user->getRememberToken());
    }

    public function testTooManyLoginRequestsAreThrottled()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->json('POST', route('auth.account.login', [
                'email' => 'fake@test.com',
                'password' => 'fake'
            ]));
        }
        $response->assertStatus(429);
    }
}
