<?php


namespace Tests\Feature;


use App\Payment\PaymentTransactionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCurrencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function testValidTransactionIsRetrievedOkay()
    {
        $this->seed();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction('00000000-0000-0000-0000-000000000001');
        $this->assertnotnull($transaction);
    }

    public function testInvalidTransactionRetrievesNull()
    {
        $this->seed();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction('00000000-0000-0000-0000-00000000000A');
        $this->assertNull($transaction);
    }

    public function testCannotAcceptAnotherUsersTransaction()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => '00000000-0000-0000-0000-000000000003'
        ]);
        $response->assertStatus(403);
    }

    public function testClosedTransactionCannotBeUsed()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => '00000000-0000-0000-0000-000000000001'
        ]);
        $response->assertStatus(403);
    }

    public function testOpenTransactionCanBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => '00000000-0000-0000-0000-000000000002'
        ]);
        $response->assertStatus(200);
    }

    public function testOpenTransactionCanBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => '00000000-0000-0000-0000-000000000002'
        ]);
        $response->assertStatus(200);
    }


    public function testClosedTransactionCannotBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => '00000000-0000-0000-0000-000000000001'
        ]);
        $response->assertStatus(403);
    }

    /**
     * @depends testOpenTransactionCanBeAccepted
     */
    public function testCompletedTransactionHasRewardedAmountRecorded()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $token = '00000000-0000-0000-0000-000000000002';
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $response->assertStatus(200);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($token);
        $this->assertNotNull($transaction->accountCurrencyRewarded);
    }
}
