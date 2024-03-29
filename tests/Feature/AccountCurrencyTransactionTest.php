<?php


namespace Tests\Feature;

use App\Payment\PaymentTransactionItem;
use App\Payment\PaymentTransactionManager;
use BillingTransactionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCurrencyTransactionTest extends TestCase
{
    use RefreshDatabase;

    private $validOwnedCompletedTransaction = '00000000-0000-0000-0000-000000000001';
    private $validOwnedOpenTransaction = '00000000-0000-0000-0000-000000000002';
    private $validUnownedTransaction = '00000000-0000-0000-0000-000000000003';
    private $validOwnedOpenTransactionWithItem = '00000000-0000-0000-0000-000000000004';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed()->seed(BillingTransactionSeeder::class);
    }

    public function testValidTransactionIsRetrievedOkay()
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($this->validOwnedCompletedTransaction);
        $this->assertnotnull($transaction);
    }

    public function testInvalidTransactionRetrievesNull()
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction('00000000-0000-0000-0000-00000000000A');
        $this->assertNull($transaction);
    }

    public function testCannotAcceptAnotherUsersTransaction()
    {
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validUnownedTransaction
        ]);
        $response->assertForbidden();
    }

    public function testClosedTransactionCannotBeUsed()
    {
        $this->loginAsValidatedUser();
        $response = $this->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedCompletedTransaction
        ]);
        $response->assertForbidden();
    }

    public function testOpenTransactionCanBeDeclined()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedOpenTransaction
        ]);
        $response->assertSuccessful();
    }

    public function testOpenTransactionCanBeAccepted()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $this->validOwnedOpenTransaction
        ]);
        $response->assertSuccessful();
    }


    public function testClosedTransactionCannotBeDeclined()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/declineTransaction', [
            'token' => $this->validOwnedCompletedTransaction
        ]);
        $response->assertForbidden();
    }

    /**
     * @depends testOpenTransactionCanBeAccepted
     */
    public function testCompletedTransactionHasRewardedAmountRecorded()
    {
        $this->loginAsValidatedUser();
        $token = $this->validOwnedOpenTransaction;
        $response = $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $response->assertSuccessful();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($token);
        $this->assertEquals('fulfilled', $transaction->result, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewarded);
    }

    /**
     * @depends testOpenTransactionCanBeAccepted
     */
    public function testCompletedTransactionWithItemsHasItemsRewardedAmountRecorded()
    {
        $this->loginAsValidatedUser();
        $token = $this->validOwnedOpenTransactionWithItem;
        $this->followingRedirects()->json('GET', 'accountcurrency/acceptTransaction', [
            'token' => $token
        ]);
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($token);
        $this->assertEquals('fulfilled', $transaction->result, "Transaction status should have been fulfilled");
        $this->assertNotNull($transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items not set.");
        $this->assertNotEquals(0, $transaction->accountCurrencyRewardedForItems,
            "Rewarded amount for items shouldn't be 0.");
    }


    public function testUserGetsOwnTransactionsInList()
    {
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayHasKey($this->validOwnedOpenTransaction, $transactions);
        $this->assertArrayHasKey($this->validOwnedCompletedTransaction, $transactions);
    }

    public function testUserDoesNotGetUnowedTransactionsInList()
    {
        $user = $this->loginAsValidatedUser();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transactions = $transactionManager->getTransactionsFor($user->getAid());
        $this->assertArrayNotHasKey($this->validUnownedTransaction, $transactions);
    }

    public function testUserCanViewOwnedTransaction()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validOwnedCompletedTransaction
        ]));
        $response->assertSuccessful();
    }

    public function testUserCannotViewUnownedTransaction()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transaction', [
            'id' => $this->validUnownedTransaction
        ]));
        $response->assertForbidden();
    }

    public function internalTestBaseAmountSavesCorrectly($transactionId)
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(10, $transaction->accountCurrencyPriceUsd, "Amount didn't save");
        $this->assertEquals(0, $transaction->itemPriceUsd, "Item price should be 0");
        $this->assertCount(0, $transaction->items, "Items should have been empty");
    }

    public function testBaseAmountOnPayPalSavesCorrectly()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newPayPalTransaction', [
            'amountUsd' => 10.0
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['token'];
        $this->internalTestBaseAmountSavesCorrectly($transactionId);
    }

    public function testBaseAmountOnCardSavesCorrectly()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newCardTransaction', [
            'cardId' => 1,
            'amountUsd' => 10.0
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['token'];
        $this->internalTestBaseAmountSavesCorrectly($transactionId);
    }

    private function internalTestItemSavesCorrectly($transactionId)
    {
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($transactionId);
        $this->assertEquals(0, $transaction->accountCurrencyPriceUsd, "Amount should be 0");
        $this->assertNotNull($transaction->itemPriceUsd, "Item price should be set");
        $this->assertNotEquals(0.0, $transaction->itemPriceUsd, "Item price should have a value");
        $this->assertNotCount(0, $transaction->items, "Items array should have an item");
        $item = $transaction->items[0];
        $this->assertTrue(is_a($item, PaymentTransactionItem::class), "Item isn't the right class");
        $this->assertEquals(1, $item->quantity, "Item should have a quantity of 1 set.");
        $this->assertNotEquals(0, $item->accountCurrencyValue, "Item should have a quoted value.");
    }

    public function testItemsOnCardSavesCorrectly()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newCardTransaction', [
            'cardId' => 1,
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['token'];
        $this->internalTestItemSavesCorrectly($transactionId);
    }

    public function testItemsOnPayPalSavesCorrectly()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('POST', 'accountcurrency/newPayPalTransaction', [
            'amountUsd' => 0.0,
            'items' => ['TESTITEM']
        ]);
        $response->assertSuccessful();
        $transactionId = (string)$response->original['token'];
        $this->internalTestItemSavesCorrectly($transactionId);
    }


    public function testUpdatedVendorTransactionIdUpdatesAndPersists()
    {
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $transactionManager->updateVendorTransactionId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $this->assertTrue($transaction->vendorTransactionId == 'NEWTEST', 'VendorTransactionId not persisted');
    }

    public function testUpdatedVendorProfileIdUpdatesAndPersists()
    {
        $this->loginAsValidatedUser();
        $transactionManager = $this->app->make(PaymentTransactionManager::class);
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $transactionManager->updateVendorProfileId($transaction, 'NEWTEST');
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not updated.');
        //Refetch
        $transaction = $transactionManager->getTransaction($this->validOwnedOpenTransaction);
        $this->assertTrue($transaction->vendorProfileId == 'NEWTEST', 'VendorProfileId not persisted');
    }

    public function testCanViewOwnTransactionHistory()
    {
        $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transactions'));
        $response->assertSuccessful();

    }

    public function testCannotViewAnothersTransactionHistory()
    {
        $user = $this->loginAsValidatedUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transactions', [
            'accountId' => $user->getAid() + 1
        ]));
        $response->assertForbidden();

    }

    public function testAdminCanViewAnothersTransactionHistory()
    {
        $user = $this->loginAsSiteAdminUser();
        $response = $this->followingRedirects()->json('GET', route('accountcurrency.transactions', [
            'accountId' => $user->getAid() + 1
        ]));
        $response->assertSuccessful();

    }
}
