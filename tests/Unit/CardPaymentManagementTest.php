<?php

namespace Tests\Unit;

use App\CardPayment\CardPaymentManager;
use Tests\TestCase;

class CardPaymentManagementTest extends TestCase
{

    protected $manager;

    protected $validDateInFuture = '11/2028';

    public function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make('App\CardPayment\CardPaymentManager');

    }

    public function testCheckLuhnChecksumIsValid()
    {
        $validValues = [
            '49927398716',
            '1234567812345670'
        ];

        $invalidValues = [
            '49927398717',
            '1234567812345678'
        ];

        foreach ($validValues as $test) {
            $this->assertTrue($this->manager->checkLuhnChecksumIsValid($test));
        }

        foreach ($invalidValues as $test) {
            $this->assertFalse($this->manager->checkLuhnChecksumIsValid($test));
        }

    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testFindIssuesWithAddCardParameters()
    {
        $tests = [
            //In the form [cardNumber, expiryDate, securityCode, expectedResult].
            // expectedResult is blank if the test is expected to pass
            ['370000000000002', $this->validDateInFuture, '123', ''], // American Express
            ['4007000000027', $this->validDateInFuture, '123', ''], // Visa
            ['6011000000000012', $this->validDateInFuture, '123', ''], // Discover
            ['3566002020360505', $this->validDateInFuture, '123', ''], // JCB
            ['5424000000000015', $this->validDateInFuture, '123', ''], // Mastercard
            //Card Number issues
            ['', $this->validDateInFuture, '123', 'Card number is required.'],
            ['4007000000027a', $this->validDateInFuture, '123', 'Card number can only contain numbers.'],
            ['4007000000072', $this->validDateInFuture, '123', 'Invalid card number.'],
            //Expiry Date issues
            ['4007000000027', '2012/12', '123', 'Expiry Date must be in the form MM/YYYY.'],
            ['4007000000027', '11/2000', '123', 'Card has expired.'],
            //Security Code issues
            ['4007000000027', $this->validDateInFuture, '', 'Security code is required.'],
            ['4007000000027', $this->validDateInFuture, '123a', 'Security code can only contain numbers.'],
            ['4007000000027', $this->validDateInFuture, '12345', 'Security code must be 3 or 4 numbers long.']


        ];
        foreach ($tests as $test) {
            [$cardNumber, $expiryDate, $securityCode, $expectedResult] = $test;
            $errors = $this->manager->findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode);
            if ($expectedResult === '') $this->assertEmpty($errors,
                'Check failed and was expected to pass. CardNumber=' . $cardNumber);
            else {
                $foundError = false;
                foreach ($errors as $error) {
                    if ($error === $expectedResult) $foundError = true;
                }
                $this->assertTrue($foundError, 'Expected error of "' . $expectedResult . '" wasn\'t returned.');
            }
        }

    }
}
