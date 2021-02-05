<?php


namespace Tests\Feature;


use App\Payment\PaymentSubscription;
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

    public function testValidSubscriptionIsRetrievedOkay()
    {
        $this->seed();
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription($this->validOwnedNewSubscription);
        $this->assertnotnull($subscription);
    }

    public function testInvalidSubscriptionRetrievesNull()
    {
        $this->seed();
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription('00000000-0000-0000-0000-00000000000A');
        $this->assertNull($subscription);
    }

    public function testCannotAcceptAnotherUsersSubscription()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }

    public function testClosedSubscriptionCannotBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedClosedSubscription
        ]);
        $response->assertForbidden();
    }

    public function testNewSubscriptionCanBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }

    public function testNewSubscriptionCanBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertSuccessful();
    }


    public function testActiveSubscriptionCannotBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedActiveSubscription
        ]);
        $response->assertForbidden();
    }

    public function testActiveCardSubscriptionCanBeCancelled()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validOwnedActiveSubscription
        ]);
        $response->assertSuccessful();
    }

    public function testUnownedActiveCardSubscriptionCannotBeCancelled()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/cancelSubscription', [
            'id' => $this->validUnownedSubscription
        ]);
        $response->assertForbidden();
    }


    public function testUserGetsOwnSubscriptionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedActiveSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedNewSubscription, $subscriptions);
        $this->assertArrayHasKey($this->validOwnedClosedSubscription, $subscriptions);
    }

    public function testUserDoesNotGetUnowedSubscriptionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscriptions = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedSubscription, $subscriptions);
    }

    public function testUserCanViewOwnedSubscription()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validOwnedActiveSubscription
        ]));
        $response->assertSuccessful();
    }

    public function testUserCannotViewUnownedSubscription()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validUnownedSubscription
        ]));
        $response->assertForbidden();
    }

    public function testUpdatedVendorProfileIdUpdatesAndPersists()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $subscriptionManager->updateVendorProfileId($subscription, 'NEWTEST');
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $subscription = $subscriptionManager->getSubscription($this->validOwnedActiveSubscription);
        $this->assertTrue($subscription->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function testActiveAndDueSubscriptionIsProcessed()
    {
        $this->seed();
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) < 5,
            "Subscription's last charge should be approximately now but is: {$subscription->lastChargeAt}");
    }

    public function testActiveSubscriptionWithARecentlyFailedTransactionDoesNotRun()
    {
        $this->seed();
        Config::set('app.process_automated_payments', true);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndFailedSubscription);
        $this->assertNull($subscription->lastChargeAt,
            "Subscription's last charge should be null but is: {$subscription->lastChargeAt}");
    }

    /**
     * @depends testActiveAndDueSubscriptionIsProcessed
     */
    public function testActiveAndDueSubscriptionIsNotProcessedIfDisabled()
    {
        $this->seed();
        Config::set('app.process_automated_payments', false);
        $this->artisan('payment:processsubscriptions')
            ->assertExitCode(0);
        $subscriptionManager = $this->app->make('App\Payment\PaymentSubscriptionManager');
        $subscription = $subscriptionManager->getSubscription($this->validOwedActiveAndDueSubscription);
        $this->assertTrue($subscription->lastChargeAt
            && $subscription->lastChargeAt->diffInMinutes(Carbon::now()) > 5,
            "Subscription's last charge should not be approximately now but is: {$subscription->lastChargeAt}");
    }


}
