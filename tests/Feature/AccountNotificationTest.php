<?php

namespace Tests\Feature;

use App\AccountNotificationManager;
use App\Muck\MuckCharacter;
use App\Notifications\MuckWebInterfaceNotification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function testNotifyingWithoutCharacterOrGameWorks()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid()
        ]);
    }

    public function testNotifyingWithGameButWithoutCharacterWorks()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid(),
            'game_code' => config('muck.muck_code')
        ]);
    }

    public function testNotifyingWithGameAndCharacterWorks()
    {
        $user = $this->loginAsValidatedUser();
        $character = new MuckCharacter(1234, 'test', 1, []);
        MuckWebInterfaceNotification::NotifyCharacter($user, $character, 'Test');
        $this->assertDatabaseHas('account_notifications', [
            'aid' => $user->getAid(),
            'game_code' => config('muck.muck_code'),
            'character_dbref' => $character->dbref()
        ]);
    }

    public function testUserGetsNotifications()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertArrayHasKey('user', $notifications);
        $this->assertCount(1, $notifications['user']);
    }

    /**
     * @depends testUserGetsNotifications
     */
    public function testUserGetsNotificationCount()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $count = $transactionManager->getNotificationCountFor($user);
        $this->assertEquals($count, 1);
    }

    /**
     * @depends testUserGetsNotifications
     */
    public function testUserDoesNotGetAnotherUsersNotifications()
    {
        $user = $this->loginAsOtherValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $user = $this->loginAsValidatedUser();
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications['user']);
    }

    /**
     * @depends testUserGetsNotifications
     */
    public function testUserDoesNotGetGameSpecificNotificationsFromAnotherGame()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyUser($user, 'Test', 50);
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications['user']);
    }

    /**
     * @depends testUserGetsNotifications
     */
    public function testUserCanDeleteOwnNotifications()
    {
        $user = $this->loginAsValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($user, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($user);
        $notification = $notifications['user'][0];
        $response = $this->delete(route('account.notifications.api') . '/' . $notification->id);
        $response->assertSuccessful();

        $notifications = $transactionManager->getNotificationsFor($user);
        $this->assertCount(0, $notifications['user'], 'Call worked but notification not deleted');
    }

    /**
     * @depends testUserGetsNotifications
     */
    public function testUserCannotDeleteOthersNotifications()
    {
        $originalUser = $this->loginAsOtherValidatedUser();
        MuckWebInterfaceNotification::NotifyAccount($originalUser, 'Test');
        $transactionManager = resolve(AccountNotificationManager::class);
        $notifications = $transactionManager->getNotificationsFor($originalUser);
        $notification = $notifications['user'][0];

        $this->loginAsValidatedUser();
        $response = $this->delete(route('account.notifications.api') . '/' . $notification->id);
        $response->assertUnauthorized();

        $notifications = $transactionManager->getNotificationsFor($originalUser);
        $this->assertCount(1, $notifications['user']);
    }

}
