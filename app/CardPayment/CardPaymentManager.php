<?php


namespace App\CardPayment;

use Illuminate\Support\Facades\DB;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class CardPaymentManager
{

    private $loginId = '';
    private $transactionKey = '';
    private $endPoint = '';

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
