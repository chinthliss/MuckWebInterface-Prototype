<?php

namespace Tests\Feature;

use App\Payment\CardPaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CardPaymentManagerTest extends TestCase
{
    /**
     * @var CardPaymentManager
     */
    private $cardPaymentManager;

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->cardPaymentManager = $this->app->make(CardPaymentManager::class);
    }

    public function testCanGetCustomerIdForUser()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $customerId = $this->cardPaymentManager->getCustomerIdFor($user);
        $this->assertNotNull($customerId);
    }

    public function testUserCannotAddInvalidCard()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $startingCardCount = count($this->cardPaymentManager->getCardsFor($user));
        $response = $this->json('POST', route('payment.cardmanagement.add', [
            'cardnumber' => '1'
        ]));
        $response->assertStatus(422);
        $this->assertEquals(count($this->cardPaymentManager->getCardsFor($user)), $startingCardCount,
            "Number of cards changed");
    }

    public function testUserCanAddValidCard()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $monthAhead = Carbon::now()->addMonth();
        $response = $this->json('POST', route('payment.cardmanagement.add'), [
            'cardNumber' => '4111111111111111',
            'expiryDate' => $monthAhead->format('m/Y'),
            'securityCode' => '123'
        ]);
        $response->assertStatus(200);
        $this->assertNotEmpty($this->cardPaymentManager->getCardsFor($user));
    }

    /**
     * @depends testUserCanAddValidCard
     */
    public function testUserCanDeleteCard()
    {
        $this->seed();
        $user = $this->loginAsValidatedUser();
        $monthAhead = Carbon::now()->addMonth();
        $this->json('POST', route('payment.cardmanagement.delete'), [
            'cardNumber' => '4111111111111111',
            'expiryDate' => $monthAhead->format('m/Y'),
            'securityCode' => '123'
        ]);
        $card = $this->cardPaymentManager->getDefaultCardFor($user);
        $this->assertNotNull($card, 'Should have gotten a valid reference after setting card.');
        $response = $this->json('DELETE', route('payment.cardmanagement.delete'), [
            'id' => $card->id
        ]);
        $response->assertStatus(200);
        $this->assertEmpty($this->cardPaymentManager->getCardsFor($user));
    }


}
