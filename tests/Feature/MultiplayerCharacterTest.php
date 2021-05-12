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
        $response = $this->get(route('home'));
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
        $response = $this->get(route('home'));
        $user = auth()->user();
        $this->assertNotNull($user->getCharacter(), "Character wasn't set on User");
        $response->assertCookie('character-dbref');
        $response->assertSee('<meta name="character-dbref" content="1234">');
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

}
