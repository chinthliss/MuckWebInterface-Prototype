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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed()->seed(PatreonSeeder::class);
    }

    public function testLoadingFromDatabaseWorks()
    {
        $patreonManager = resolve(PatreonManager::class);
        $pledges = $patreonManager->getPatrons();
        $this->assertNotEmpty($pledges);
    }

    public function testLoadingLegacyClaimsFromDatabaseWorks()
    {
        $patreonManager = resolve(PatreonManager::class);
        $claims = $patreonManager->getLegacyclaims();
        $this->assertNotEmpty($claims);
    }

    public function testPreviousContributionsCalculatedCorrectly()
    {
        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $previousAmountCents = $patron->memberships[1]->rewardedCents;
        $this->assertTrue($previousAmountCents == 150,
            "Previous claims didn't total correctly. Should have been 150, was {$previousAmountCents}.");
    }

    public function testLegacyClaimsDoNotSendNotification()
    {
        Notification::fake();
        $this->artisan('patreon:convertlegacy')
            ->assertExitCode(0);
        Notification::assertNothingSent();
    }

    public function testLegacyClaimsTotalCorrectly()
    {
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
        Config::set('app.process_automated_payments', true);

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
        Config::set('app.process_automated_payments', false);

        $this->artisan('patreon:processrewards')
            ->assertExitCode(0);

        $patreonManager = resolve(PatreonManager::class);
        $patron = $patreonManager->getPatron(1);
        $this->assertNotEquals($patron->memberships[1]->lifetimeSupportCents, $patron->memberships[1]->rewardedCents);
    }

}
