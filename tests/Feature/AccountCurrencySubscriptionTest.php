<?php


namespace Tests\Feature;


use App\Payment\PaymentSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCurrencySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private $validOwnedNewSubscription = '00000000-0000-0000-0000-000000000001';
    private $validOwnedActiveSubscription = '00000000-0000-0000-0000-000000000002';
    private $validOwnedClosedSubscription = '00000000-0000-0000-0000-000000000003';
    private $validUnownedSubscription = '00000000-0000-0000-0000-000000000004';

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
        $response->assertStatus(403);
    }

    public function testClosedSubscriptionCannotBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedClosedSubscription
        ]);
        $response->assertStatus(403);
    }

    public function testNewSubscriptionCanBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertStatus(200);
    }

    public function testNewSubscriptionCanBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptSubscription', [
            'token' => $this->validOwnedNewSubscription
        ]);
        $response->assertStatus(200);
    }


    public function testActiveSubscriptionCannotBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineSubscription', [
            'token' => $this->validOwnedActiveSubscription
        ]);
        $response->assertStatus(403);
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
        $response->assertStatus(200);
    }

    public function testUserCannotViewUnownedSubscription()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.subscription', [
            'id' => $this->validUnownedSubscription
        ]));
        $response->assertStatus(403);
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

}