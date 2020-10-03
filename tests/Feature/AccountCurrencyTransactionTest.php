<?php


namespace Tests\Feature;


use App\Payment\PaymentTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCurrencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    private $validOwnedCompletedTransaction = '00000000-0000-0000-0000-000000000001';
    private $validOwnedOpenTransaction = '00000000-0000-0000-0000-000000000002';
    private $validUnownedTransaction = '00000000-0000-0000-0000-000000000003';
    private $validOwnedOpenTransactionWithItem = '00000000-0000-0000-0000-000000000004';

    public function testValidTransactionIsRetrievedOkay()
    {
        $this->seed();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedCompletedTransaction);
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
            'token' => $this->validUnownedTransaction
        ]);
        $response->assertStatus(403);
    }

    public function testClosedTransactionCannotBeUsed()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedCompletedTransaction
        ]);
        $response->assertStatus(403);
    }

    public function testOpenTransactionCanBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedOpenTransaction
        ]);
        $response->assertStatus(200);
    }

    public function testOpenTransactionCanBeAccepted()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedOpenTransaction
        ]);
        $response->assertStatus(200);
    }


    public function testClosedTransactionCannotBeDeclined()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedCompletedTransaction
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
        $token = $this->validOwnedOpenTransaction;
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $response->assertStatus(200);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($token);
        $this->assertEquals('fulfilled', $transaction->status, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewarded);
    }

    /**
     * @depends testOpenTransactionCanBeAccepted
     */
    public function testCompletedTransactionWithItemsHasItemsRewardedAmountRecorded()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $token = $this->validOwnedOpenTransactionWithItem;
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($token);
        $this->assertEquals('fulfilled', $transaction->status, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items not set.");
        $this->assertNotEquals(0, $transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items shouldn't be 0.");
    }


    public function testUserGetsOwnTransactionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedOpenTransaction, $transactions);
        $this->assertArrayHasKey($this->validOwnedCompletedTransaction, $transactions);
    }

    public function testUserDoesNotGetUnowedTransactionsInList()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedTransaction, $transactions);
    }

    public function testUserCanViewOwnedTransaction()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validOwnedCompletedTransaction
        ]));
        $response->assertStatus(200);
    }

    public function testUserCannotViewUnownedTransaction()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validUnownedTransaction
        ]));
        $response->assertStatus(403);
    }

    public function internalTestBaseAmountSavesCorrectly($transactionId)
    {
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(10, $transaction->accountCurrencyPriceUsd, "Amount didn't save");
        $this->assertEquals(0, $transaction->itemPriceUsd, "Item price should be 0");
        $this->assertCount(0, $transaction->items, "Items should have been empty");
    }

    public function testBaseAmountOnPayPalSavesCorrectly()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newPayPalTransaction', [
            'amountUsd' => 10.0
        ]);
        $response->assertStatus(200);
        $transactionId = (string)$response->original['token'];
        $this->internalTestBaseAmountSavesCorrectly($transactionId);
    }

    public function testBaseAmountOnCardSavesCorrectly()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newCardTransaction', [
            'cardId' => 1,
            'amountUsd' => 10.0
        ]);
        $response->assertStatus(200);
        $transactionId = (string)$response->original['token'];
        $this->internalTestBaseAmountSavesCorrectly($transactionId);
    }

    private function internalTestItemSavesCorrectly($transactionId)
    {
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(0, $transaction->accountCurrencyPriceUsd, "Amount should be 0");
        $this->assertNotNull($transaction->itemPriceUsd, "Item price should be set");
        $this->assertNotEquals(0.0, $transaction->itemPriceUsd, "Item price should have a value");
        $this->assertNotCount(0, $transaction->items, "Items array should have an item");
        $item = $transaction->items[0];
        $this->assertTrue(is_a($item, PaymentTransactionItem::class), "Item isn't the right class");
        $this->assertEquals(1, $item->quantity, "Item should have a quantity of 1 set.");
        $this->assertNotEquals(0,$item->accountCurrencyValue, "Item should have a quoted value.");
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


    public function testUpdatedVendorTransactionIdUpdatesAndPersists()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $transactionManager->updateVendorTransactionId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not persisted');
    }

    public function testUpdatedVendorProfileIdUpdatesAndPersists()
    {
        $this->seed();
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make('App\Payment\PaymentTransactionManager');
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $transactionManager->updateVendorProfileId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

}
