<?php

namespace Tests\Feature;

use App\Payment\PatreonManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use PatreonSeeder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatreonTest extends TestCase
{
    use refreshDatabase;

    /**
     * Tests the basically functionality for patreon.
     *
     * @return void
     */
    public function testLoadingFromDatabaseWorks()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);
        $patreonManager = resolve(PatreonManager::class);
        $pledges = $patreonManager->getPatrons();
        $this->assertNotEmpty($pledges);
    }

    public function testLoadingLegacyClaimsFromDatabaseWorks()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);
        $patreonManager = resolve(PatreonManager::class);
        $claims = $patreonManager->getLegacyclaims();
        $this->assertNotEmpty($claims);
    }

    public function testPreviousContributionsCalculatedCorrectly()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);
        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $previousAmountCents = $patron->memberships[1]->rewardedCents;
        $this->assertTrue($previousAmountCents == 150,
            "Previous claims didn't total correctly. Should have been 150, was {$previousAmountCents}.");
    }

    public function testLegacyClaimsDoNotSendNotification()
    {
        Notification::fake();
        $this->seed();
        $this->seed(PatreonSeeder::class);
        $this->artisan('patreon:convertlegacy')
            ->assertExitCode(0);
        Notification::assertNothingSent();
    }

    public function testLegacyClaimsTotalCorrectly()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(150, $patron->memberships[1]->rewardedCents);

        $this->artisan('patreon:convertlegacy')
            ->assertExitCode(0);

        $patreonManager->clearCache();
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(250, $patron->memberships[1]->rewardedCents);
    }

    public function testRewardsProcessCorrectly()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals(150, $patron->memberships[1]->rewardedCents);

        $this->artisan('patreon:processrewards')
            ->assertExitCode(0);

        $patreonManager->clearCache();
        $patron = $patreonManager->getPatron(1);
        $this->assertEquals($patron->memberships[1]->lifetimeSupportCents, $patron->memberships[1]->rewardedCents);
    }

    /**
     * @depends testRewardsProcessCorrectly
     */
    public function testRewardsDoNotProcessWhenDisabled()
    {
        $this->seed();
        $this->seed(PatreonSeeder::class);
        Config::set('app.process_automated_payments', false);

        $this->artisan('patreon:processrewards')
            ->assertExitCode(0);

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertNotEquals($patron->memberships[1]->lifetimeSupportCents, $patron->memberships[1]->rewardedCents);
    }

}
