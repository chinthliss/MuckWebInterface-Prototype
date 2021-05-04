<?php


namespace Tests\Feature;

use App\Payment\PaymentSubscriptionManager;
use BillingSubscriptionSeeder;
use BillingTransactionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AccountCurrencySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private $validOwnedNewSubscription = '00000000-0000-0000-0000-000000000001';
    private $validOwnedActiveSubscription = '00000000-0000-0000-0000-000000000002';
    private $validOwnedClosedSubscription = '00000000-0000-0000-0000-000000000003';
    private $validUnownedSubscription = '00000000-0000-0000-0000-000000000004';
    private $validOwedActiveAndDueSubscription = '00000000-0000-0000-0000-000000000005';
    private $validOwedActiveAndFailedSubscription = '00000000-0000-0000-0000-000000000006';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed()->seed(BillingTransactionSeeder::class)->seed(BillingSubscriptionSeeder::class);
    }

    public function testValidSubscriptionIsRetrievedOkay()
    {
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwnedNewSubscription);
        $this->assertnotnull($subscription);
    }

    public function testInvalidSubscriptionRetrievesNull()
    {
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription('00000000-0000-0000-0000-00000000000A');
        $this->assertNull($subscription);
    }

    public function testCannotAcceptAnotherUsersSubscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }

    public function testClosedSubscriptionCannotBeAccepted()
    {
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedClosedSubscription
        ]);
        $response->assertForbidden();
    }

    public function testNewSubscriptionCanBeDeclined()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }

    public function testNewSubscriptionCanBeAccepted()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }


    public function testActiveSubscriptionCannotBeDeclined()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedActiveSubscription
        ]);
        $response->assertForbidden();
    }

    public function testActiveCardSubscriptionCanBeCancelled()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validOwnedActiveSubscription
        ]);
        $response->assertSuccessful();
    }

    public function testUnownedActiveCardSubscriptionCannotBeCancelled()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }


    public function testUserGetsOwnSubscriptionsInList()
    {
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedActiveSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedNewSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedClosedSubscription, $subscriptions);
    }

    public function testUserDoesNotGetUnownedSubscriptionsInList()
    {
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedSubscription, $subscriptions);
    }

    public function testUserCanViewOwnedSubscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validOwnedActiveSubscription
        ]));
        $response->assertSuccessful();
    }

    public function testUserCannotViewUnownedSubscription()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validUnownedSubscription
        ]));
        $response->assertForbidden();
    }

    public function testUpdatedVendorProfileIdUpdatesAndPersists()
    {
        $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $subscriptionManager->updateVendorProfileId($subscription, 'NEWTEST');
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function testActiveAndDueSubscriptionIsProcessed()
    {
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) < 5,
            "Subscription's last charge should be approximately now but is: {$subscription->lastChargeAt}");
    }

    public function testActiveSubscriptionWithARecentlyFailedTransactionDoesNotRun()
    {
        Config::set('app.process_automated_payments', true);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndFailedSubscription);
        $this->assertNull($subscription->lastChargeAt,
            "Subscription's last charge should be null but is: {$subscription->lastChargeAt}");
    }

    /**
     * @depends testActiveAndDueSubscriptionIsProcessed
     */
    public function testActiveAndDueSubscriptionIsNotProcessedIfDisabled()
    {
        Config::set('app.process_automated_payments', false);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make(PaymentSubscriptionManager::class);
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) > 5,
            "Subscription's last charge should not be approximately now but is: {$subscription->lastChargeAt}");
    }


}
