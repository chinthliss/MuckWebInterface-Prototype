<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HostLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testHostLoggedWithNoLogin()
    {
        $this->get('/');
        $this->assertDatabaseHas('log_hosts', ['aid' => 0]);
    }

    public function testHostLoggedWithLoginButNoCharacter()
    {
        $user = $this->loginAsValidatedUser();
        $this->get('/');
        $this->assertDatabaseHas('log_hosts', ['aid' => $user->getAid()]);
    }

    public function testHostLoggedWithLoginAndCharacter()
    {
        $user = $this->loginAsValidatedUser();
        $this->post(route('multiplayer.character.set'), ['dbref' => 1234]);
        $this->get('/');
        $this->assertDatabaseHas('log_hosts', ['aid' => $user->getAid(), 'plyr_ref' => 1234]);
    }

}

