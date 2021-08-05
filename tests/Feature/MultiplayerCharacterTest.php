<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiplayerCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testNotInitiallySet()
    {
        $response = $this->get(route('multiplayer.home'));
        $response->assertDontSee('<meta name="character-dbref"');
        //$response->assertSessionMissing('character-dbref');
        $response->assertCookieMissing('character-dbref');
    }

    public function testSetCharacterWorksWithAccountAlreadyLoggedIn()
    {

        $user = $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref', 1234);

        //On the next call, the character should have loaded again and the header should be set
        $response = $this->get(route('multiplayer.home'));
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref');
        $response->assertSee('<meta name="character-dbref" content="1234">', false);
    }

    /**
     * @depends testSetCharacterWorksWithAccountAlreadyLoggedIn
     */
    public function testSetCharacterDoesNotWorkWithInvalidCharacter()
    {
        $user = $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.set'), ['dbref' => 5234]);
        $this->assertNull($user->getCharacter(), "Character was set on User");
        $response->assertCookieMissing('character-dbref');
    }

    // Related - AccountLoginTest::testCanLoginWithCorrectMuckCredentials
    public function testSetCharacterDuringLoginWorks()
    {
        $response = $this->json('POST', route('auth.account.login', [
            'email' => 'testCharacter',
            'password' => 'password'
        ]));
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref');
    }

    public function testPageThatRequiresCharacterRedirectsToSelectionAndThenReturns()
    {
        $this->loginAsValidatedUser();
        $request = $this->get(route('multiplayer.avatar'));
        $request->assertRedirect(route('multiplayer.character.select'));
        $response = $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        // Check redirectUrl is to the originally wanted page
        $response->assertJsonFragment(['redirectUrl' => route('multiplayer.avatar')]);
    }

    public function testPageThatRequiresCharacterWorksWithCharacter()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $request = $this->get(route('multiplayer.avatar'));
        $request->assertOk();
    }

    public function testUnapprovedCharacterIsRedirectedToCharacterGeneration()
    {
        $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 3456]);
        $request = $this->get(route('multiplayer.avatar'));
        $request->assertRedirect(route('multiplayer.character.generate'));
    }

    #region Change Character Password

    public function testChangeCharacterPasswordWorks()
    {
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertRedirect(route('multiplayer.character.select'));
        $response->assertSessionHas('message-success');
        $response->assertSessionHasNoErrors();
    }

    public function testChangeCharacterPasswordDoesNotAllowChangingAnotherUsers()
    {
        $this->loginAsOtherValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'password',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    public function testChangeCharacterPasswordRequiresAccountPassword()
    {
        $this->loginAsValidatedUser();
        $response = $this->post(route('multiplayer.character.changepassword'), [
            'accountpassword' => 'wrongpassword',
            'character' => 1234,
            'password' => 'newpassword'
        ]);
        $response->assertSessionHasErrors();
    }

    #endregion Change Character Password
}
