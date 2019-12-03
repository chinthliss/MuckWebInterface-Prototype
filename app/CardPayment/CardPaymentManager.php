<?php


namespace App\CardPayment;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

//Technically this should be split into core management things and an authenticate.net provider.
//Don't have enough experience with payment providers to do this though.
class CardPaymentManager
{

    private $loginId = '';
    private $transactionKey = '';
    private $endPoint = '';

    const CARD_TYPE_MATCHES = [
        "VISA" => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        "American Express" => '/^3[47][0-9]{13}$/',
        "JCB" => '/^(?:2131|1800|35\d{3})\d{11}$/',
        "Maesto" => '/^(5018|5020|5038|6304|6759|6761|6763)[0-9]{8,15}$/',
        "Mastercard" => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/'
        //Solo and Switch removed from this list due to being discontinued.
    ];

    /**
     * Class used to hold customer details
     * @var CardPaymentCustomerProfile|null
     */
    private $customerProfileModel = null;

    /**
     * @var array<int, CardPaymentCustomerProfile>
     */
    private $customerProfiles = [];

    /**
     * Authentication passed through on each request
     * @var AnetAPI\MerchantAuthenticationType|null
     */
    private $merchantAuthentication = null;

    public function __construct(string $loginId, string $transactionKey, string $endPoint,
                                string $cardPaymentCustomerProfileModel)
    {
        $this->loginId = $loginId;
        $this->transactionKey = $transactionKey;
        $this->endPoint = $endPoint;
        $this->customerProfileModel = $cardPaymentCustomerProfileModel;
    }

    private function refId()
    {
        return 'ref' . time();
    }

    private function merchantAuthentication()
    {
        if (!$this->merchantAuthentication) {
            $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
            $this->merchantAuthentication->setName($this->loginId);
            $this->merchantAuthentication->setTransactionKey($this->transactionKey);
        }
        return $this->merchantAuthentication;
    }

    /**
     * Loads customer profile, any customer payment profiles and subscription ids.
     * Returns the ProfileID or null if there was no profile to load
     * @param int $accountId
     * @return CardPaymentCustomerProfile|null
     */
    public function loadProfileFor(int $accountId)
    {
        //Return if already fetched
        if (array_key_exists($accountId, $this->customerProfiles)) return $this->customerProfiles[$accountId];

        /** @var CardPaymentCustomerProfile $profile */
        $profile = null;

        //Attempt to find ID in database
        $row = DB::table('billing_profiles')->where('aid', $accountId)->first();
        if ($row) {
            $profileId = $row['profileid'];
            $request = new AnetAPI\GetCustomerProfileRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication());
            $request->setCustomerProfileId($profileId);
            $controller = new AnetController\GetCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->endPoint);
            if ($response && $response->getMessages()->getResultCode() == "Ok") {
                $profile = $this->customerProfileModel::fromApiResponse($response);
                if($profile->getMerchantCustomerId() != $accountId) {
                    // $profile = null;
                    Log::warn("Retrieved Authorize.net customer profile for AID " . $accountId . " didn't have a matching merchantId.");
                }
            }
        }
        $this->customerProfiles[$accountId] = $profile;
        return $profile;
    }

    /**
     * @param string|int $number
     * @return bool
     */
    public function checkLuhnChecksumIsValid($number)
    {
        $total = 0;
        foreach (str_split(strrev(strval($number))) as $index => $character) {
            $total += ($index % 2 == 0 ? $character : array_sum(str_split(strval($character * 2))));
        }
        return ($total % 10 == 0);
    }

    //Returns blank array if everything is okay, otherwise returns errors in the form { <element>:"error" }
    public function findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode)
    {
        $errors = [];

        //Card Number checks
        $cardNumber = str_replace(' ', '', $cardNumber);
        if ($cardNumber == '')
            $errors['cardNumber'] = 'Card number is required.';
        else {
            $cardType = "";
            foreach (self::CARD_TYPE_MATCHES as $testingFor => $cardTypeTest) {
                if (preg_match($cardTypeTest, $cardNumber)) $cardType = $testingFor;
            }
            if (!$cardType) $errors['cardNumber'] = 'Unrecognized card number.';
            else {
                if (!$this->checkLuhnChecksumIsValid($cardNumber)) $errors['cardNumber'] = 'Invalid card number.';
            }
        }

        //Expiry Date checks
        if (!preg_match('/^\d\d\/\d\d\d\d$/', $expiryDate)) {
            $errors['expiryDate'] = 'Expiry Date must be in the form MM/YYYY';
        } else {
            [$month, $year] = explode('/', $expiryDate);

            $actualDate = Carbon::createFromDate($year, $month, 1);
            if ($actualDate < Carbon::now()) {
                $errors['expiryDate'] = 'Card has expired.';
            }
        }

        //Security Code checks
        if ($securityCode == '')
            $errors['securityCode'] = 'Security code is required.';
        else {
            if (strlen($securityCode) < 3 or strlen($securityCode) > 4)
                $errors['securityCode'] = 'Security code must be 3 or 4 numbers long.';
        }

        return $errors;
    }

    public function test()
    {
        $merchantAuthentication = $this->merchantAuthentication();
        $refId = 'ref' . time();

        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber("4111111111111111" );
        $creditCard->setExpirationDate( "2038-12");
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount(151.51);
        $transactionRequestType->setPayment($paymentOne);
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);
        return $response;
    }
}