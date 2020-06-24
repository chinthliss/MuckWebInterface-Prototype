<?php

namespace Tests\Unit;

use App\Payment\CardPaymentManager;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CardPaymentManagementTest extends TestCase
{

    protected $manager;

    public function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->app->make('App\Payment\CardPaymentManager');

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
        $presentMonth = Carbon::now()->format('m/Y');
        $validDateInFuture = Carbon::now()->addYear()->format('m/Y');
        $tests = [
            //In the form [cardNumber, expiryDate, securityCode, expectedResult].
            // expectedResult is blank if the test is expected to pass
            ['370000000000002', $validDateInFuture, '123', ''], // American Express
            ['4007000000027', $validDateInFuture, '123', ''], // Visa
            ['3566002020360505', $validDateInFuture, '123', ''], // JCB
            ['5424000000000015', $validDateInFuture, '123', ''], // Mastercard
            //Card Number issues
            ['', $validDateInFuture, '123', 'Card number is required.'],
            ['4007000000027a', $validDateInFuture, '123', 'Card number can only contain numbers.'],
            ['4007000000072', $validDateInFuture, '123', 'Invalid card number.'],
            //Expiry Date issues
            ['4007000000027', '2012/12', '123', 'Expiry Date must be in the form MM/YYYY.'],
            ['4007000000027', '11/2000', '123', 'Card has expired.'],
            //Security Code issues
            ['4007000000027', $validDateInFuture, '', 'Security code is required.'],
            ['4007000000027', $validDateInFuture, '123a', 'Security code can only contain numbers.'],
            ['4007000000027', $validDateInFuture, '12345', 'Security code must be 3 or 4 numbers long.'],
            //End of present month - still valid
            ['4007000000027', $presentMonth , '123', ''] // Visa
        ];
        foreach ($tests as $test) {
            [$cardNumber, $expiryDate, $securityCode, $expectedResult] = $test;
            $errors = $this->manager->findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode);
            if ($expectedResult === '') $this->assertEmpty($errors,
                'Check failed and was expected to pass.'
                . ' CardNumber=' . $cardNumber
                . ', Expiry=' . $expiryDate
                . ', SecurityCode=' . $securityCode
                . ". Errors = " . json_encode($errors)
            );
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
