<?php


namespace Tests\Feature;


use App\Payment\PaymentTransactionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCurrencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    private $validOwnedCompletedTransation = '00000000-0000-0000-0000-000000000001';
    private $validOwnedOpenTransation = '00000000-0000-0000-0000-000000000002';
    private $validUnownedTransation = '00000000-0000-0000-0000-000000000003';

    public function testValidTransactionIsRetrievedOkay()
    {
        $this->seed();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedCompletedTransation);
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
            'token' => $this->validUnownedTransation
        ]);
        $response->assertStatus(403);
    }

    public function testClosedTransactionCannotBeUsed()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedCompletedTransation
        ]);
        $response->assertStatus(403);
    }

    public function testOpenTransactionCanBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedOpenTransation
        ]);
        $response->assertStatus(200);
    }

    public function testOpenTransactionCanBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedOpenTransation
        ]);
        $response->assertStatus(200);
    }


    public function testClosedTransactionCannotBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedCompletedTransation
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
        $token = $this->validOwnedOpenTransation;
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $response->assertStatus(200);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($token);
        $this->assertNotNull($transaction->accountCurrencyRewarded);
    }

    public function testUserGetsOwnTransactionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedOpenTransation, $transactions);
        $this->assertArrayHasKey($this->validOwnedCompletedTransation, $transactions);
    }

    public function testUserDoesNotUnowedTransactionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedTransation, $transactions);
    }

    public function testUserCanViewOwnedTransaction()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validOwnedCompletedTransation
        ]));
        $response->assertStatus(200);
    }

    public function testUserCannotViewUnownedTransaction()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validUnownedTransation
        ]));
        $response->assertStatus(403);
    }

    public function testUpdatedExternalIdUpdatesAndPersists()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransation);
        $transactionManager->updateExternalId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->externalId == 'NEWTEST', 'ExternalId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransation);
        $this->assertTrue($transaction->externalId == 'NEWTEST', 'ExternalId not persisted');
    }

    public function testUpdatedPaymentProfileIdUpdatesAndPersists()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransation);
        $transactionManager->updatePaymentProfileId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->paymentProfileId == 'NEWTEST', 'PaymentProfileId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransation);
        $this->assertTrue($transaction->paymentProfileId == 'NEWTEST', 'PaymentProfileId not persisted');
    }

}
