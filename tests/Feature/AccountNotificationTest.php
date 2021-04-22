<?php

namespace Tests\Feature;

use App\Muck\MuckCharacter;
use App\Notifications\MuckWebInterfaceNotification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function testNotifyingWithoutCharacterOrGameWorks()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid()
        ]);
    }

    public function testNotifyingWithGameButWithoutCharacterWorks()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid(),
            'game_code' => config('muck.muck_code')
        ]);
    }

    public function testNotifyingWithGameAndCharacterWorks()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $character = new MuckCharacter(1234, 'test', 1, []);
        MuckWebInterfaceNotification::NotifyCharacter($user, $character, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid(),
            'game_code' => config('muck.muck_code'),
            'character_dbref' => $character->getDbref()
        ]);
    }
}
