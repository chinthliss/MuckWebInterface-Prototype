<?php


namespace Tests\Feature;


use App\Payment\PaymentTransactionItem;
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

    public function testUserDoesNotGetUnowedTransactionsInList()
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

    public function testBaseAmountOnPayPalSavesCorrectly()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newPayPalTransaction', [
            'amountUsd' => 10.0
        ]);
        $response->assertStatus(200);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $id = (string)$response->original['token'];
        $transaction = $transactionManager->getTransaction($id);
        $this->assertTrue($transaction->accountCurrencyPriceUsd == 10, "Amount didn't save");
        $this->assertTrue(!$transaction->itemPriceUsd, "Item amount should have been zero or null");
        $this->assertTrue(!$transaction->items, "Items should have been empty");
    }

    public function testBaseAmountOnCardSavesCorrectly()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newCardTransaction', [
            'cardId' => 1,
            'amountUsd' => 10.0
        ]);
        $response->assertStatus(200);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $id = (string)$response->original['token'];
        $transaction = $transactionManager->getTransaction($id);
        $this->assertTrue($transaction->accountCurrencyPriceUsd == 10, "Amount didn't save");
        $this->assertTrue(!$transaction->itemPriceUsd, "Item amount should have been zero or null");
        $this->assertTrue(!$transaction->items, "Items should have been empty");
    }

    private function internalTestItemSavesCorrectly($transactionId)
    {
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertTrue($transaction->accountCurrencyPriceUsd == 0, "Amount should be 0");
        $this->assertTrue($transaction->itemPriceUsd > 0.0, "Item price should have a value");
        $this->assertTrue(count($transaction->items) > 0, "Items array should have an item");
        $item = $transaction->items[0];
        $this->assertTrue(is_a($item, PaymentTransactionItem::class), "Item isn't the right class");
        $this->assertTrue($item->quantity == 1, "Item should have a quantity of 1 set.");
        $this->assertTrue($item->accountCurrencyValue > 0, "Item should have a quoted value.");
    }

    public function testItemsOnCardSavesCorrectly()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newCardTransaction', [
            'cardId' => 1,
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertStatus(200);
        $transactionId = (string)$response->original['token'];
        $this->internalTestItemSavesCorrectly($transactionId);
    }

    public function testItemsOnPayPalSavesCorrectly()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newPayPalTransaction', [
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertStatus(200);
        $transactionId = (string)$response->original['token'];
        $this->internalTestItemSavesCorrectly($transactionId);
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
