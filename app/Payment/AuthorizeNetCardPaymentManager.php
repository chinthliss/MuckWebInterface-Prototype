<?php


namespace App\Payment;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeNetCardPaymentManager implements CardPaymentManager
{

    const CARD_TYPE_MATCHES = [
        "VISA" => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        "American Express" => '/^3[47][0-9]{13}$/',
        "JCB" => '/^(?:2131|1800|35\d{3})\d{11}$/',
        // "Discover" => '/^(?:6011\d{12})|(?:65\d{14})$/', // Not accepted by us
        "Mastercard" => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/'
        //Solo, Switch removed from this list due to being discontinued. Maestro removed as not actually accepted by Authorize.net
    ];

    private $loginId = '';
    private $transactionKey = '';
    private $endPoint = '';

    /**
     * @var array<int, AuthorizeNetCardPaymentCustomerProfile>
     */
    private $customerProfiles = [];

    /**
     * Authentication passed through on each request
     * @var AnetAPI\MerchantAuthenticationType|null
     */
    private $merchantAuthentication = null;

    public function __construct(array $config)
    {
        $endPoint = null;
        if (App::environment() !== 'production') //Not ideal but it's where they stored it.
            $endPoint = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        else
            $endPoint = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        if (!$config['loginId'] || !$config['transactionKey'])
            throw new \Error('Configuration for Authorize.Net is missing loginId or transactionKey');
        $this->loginId = $config['loginId'];
        $this->transactionKey = $config['transactionKey'];
        $this->endPoint = $endPoint;
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
        $cardNumber = str_replace([' ', '-'], '', $cardNumber);
        if ($cardNumber == '')
            $errors['cardNumber'] = 'Card number is required.';
        else {
            if (!is_numeric($cardNumber)) $errors['cardNumber'] = 'Card number can only contain numbers.';
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
        }

        //Expiry Date checks
        if (!preg_match('/^\d\d\/\d\d\d\d$/', $expiryDate)) {
            $errors['expiryDate'] = 'Expiry Date must be in the form MM/YYYY.';
        } else {
            [$month, $year] = explode('/', $expiryDate);

            $endDate = Carbon::createFromDate($year, $month + 1, 1);
            if ($endDate < Carbon::now()) {
                $errors['expiryDate'] = 'Card has expired.';
            }
        }

        //Security Code checks
        if ($securityCode == '')
            $errors['securityCode'] = 'Security code is required.';
        else {
            if (!is_numeric($securityCode))
                $errors['securityCode'] = 'Security code can only contain numbers.';
            else if (strlen($securityCode) < 3 or strlen($securityCode) > 4)
                $errors['securityCode'] = 'Security code must be 3 or 4 numbers long.';
        }

        return $errors;
    }

    /**
     * Loads customer profile, any customer payment profiles and subscription ids.
     * Returns the profile or null if there was no profile to load
     * @param User $user
     * @return AuthorizeNetCardPaymentCustomerProfile|null
     */
    private function loadProfileFor(User $user)
    {
        $accountId = $user->getAid();
        //Return if already fetched
        if (array_key_exists($accountId, $this->customerProfiles)) return $this->customerProfiles[$accountId];

        /** @var AuthorizeNetCardPaymentCustomerProfile $profile */
        $profile = null;

        //Attempt to find ID in database
        $row = DB::table('billing_profiles')->where('aid', $accountId)->first();
        if ($row) {
            $profileId = $row->profileid;
            $request = new AnetAPI\GetCustomerProfileRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication());
            $request->setCustomerProfileId($profileId);
            $controller = new AnetController\GetCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->endPoint);
            if ($response && $response->getMessages()->getResultCode() == "Ok") {
                $profile = AuthorizeNetCardPaymentCustomerProfile::fromApiResponse($response);
                if ($profile->getMerchantCustomerId() != $accountId) {
                    // $profile = null;
                    Log::warning("Retrieved Authorize.net customer profile for AID " . $accountId . " didn't have a matching merchantId.");
                }
                // Need to populate full card details from what we know, since ANet response masks expiry dates.
                $paymentProfiles = DB::table('billing_paymentprofiles')
                    ->where('profileid', $profile->getCustomerProfileId())->get();
                foreach ($paymentProfiles as $paymentProfile) {
                    $present = $profile->getCard($paymentProfile->paymentid);
                    if ($present) {
                        $present->expiryDate = $paymentProfile->expdate;
                        $profile->setCard($present);
                    }

                }
                // Subscriptions
                $subscriptions = $response->getSubscriptionIds();
                if ($subscriptions) {
                    //TODO Retrieve subscription
                    dd("Subscription found!");
                }
                // Historic thing - default is controlled by the muck (But we'll set it on ANet going forwards)
                $defaultCardId = DB::table('billing_profiles')
                    ->leftJoin('billing_paymentprofiles', 'billing_profiles.defaultcard', '=', 'billing_paymentprofiles.id')
                    ->where('billing_profiles.profileid', '=', $profile->getCustomerProfileId())
                    ->value('billing_paymentprofiles.paymentid');
                if ($defaultCardId) {
                    foreach ($profile->getCards() as $card) {
                        $card->isDefault = $card->id == $defaultCardId;
                    }
                }
            }
        }
        $this->customerProfiles[$accountId] = $profile;
        return $profile;
    }

    /**
     * Loads customer profile, any customer payment profiles and subscription ids.
     * If such doesn't exist, creates an entry for them.
     * @param User $user
     * @return AuthorizeNetCardPaymentCustomerProfile
     */
    private function loadOrCreateProfileFor(User $user)
    {
        $profile = $this->loadProfileFor($user);
        if (!$profile) {
            $anetProfile = new AnetAPI\CustomerProfileType();
            $anetProfile->setDescription("");
            $anetProfile->setMerchantCustomerId($user->getAid());
            $anetProfile->setEmail($user->getEmailForVerification());
            $request = new AnetAPI\CreateCustomerProfileRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication());
            $request->setRefId($this->refId());
            $request->setProfile($anetProfile);
            $controller = new AnetController\CreateCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->endPoint);
            if ($response && ($response->getMessages()->getResultCode() == "Ok")) {
                $profile = AuthorizeNetCardPaymentCustomerProfile::fromApiResponse($response);
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                throw new \Exception("Couldn't create a profile. Response : "
                    . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
            }
            DB::table('billing_profiles')->insert([
                'aid' => $user->getAid(),
                'profileid' => $profile->getCustomerProfileId(),
                'defaultcard' => 0,
                'spendinglimit' => 0
            ]);
        }
        return $profile;
    }

    /**
     * @inheritDoc
     */
    public function createCardFor(User $user, $cardNumber,
                                  $expiryDate, $securityCode): Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $anetCard = new AnetAPI\CreditCardType();
        $anetCard->setCardNumber($cardNumber);
        $anetCard->setExpirationDate($expiryDate);
        $anetCard->setCardCode($securityCode);

        $anetPaymentCard = new AnetAPI\PaymentType();
        $anetPaymentCard->setCreditCard($anetCard);

        //Previous code set a dummy address - not sure if this will remain valid?
        $anetAddress = new AnetAPI\CustomerAddressType();
        $anetAddress->setAddress("123 Not Available");
        $anetAddress->setZip("00000");

        $anetPaymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $anetPaymentProfile->setCustomerType('individual');
        $anetPaymentProfile->setBillTo($anetAddress);
        $anetPaymentProfile->setPayment($anetPaymentCard);
        $anetPaymentProfile->setDefaultPaymentProfile(true);

        // Make the request
        $request = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        // Add an existing profile id to the request
        $request->setCustomerProfileId($profile->getCustomerProfileId());
        $request->setPaymentProfile($anetPaymentProfile);
        $request->setValidationMode("liveMode");
        // Create the controller and get the response
        $controller = new AnetController\CreateCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);
        if (!$response || ($response->getMessages()->getResultCode() != "Ok")) {
            $errorMessages = $response->getMessages()->getMessage();
            if (count($errorMessages) == 1 && $errorMessages[0]->getCode() === 'E00027') {
                // E00027 - The transaction was unsuccessful.
                throw new \InvalidArgumentException("The transaction was unsuccessful.");
            } else
                throw new \Exception("Couldn't create a payment profile. Response : "
                    . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
        }
        $card = new Card();
        $card->id = $response->getCustomerPaymentProfileId();
        //Silly that this has to be extracted from a huge comma separated string..
        $responseParts = explode(',', $response->getValidationDirectResponse());
        $card->cardNumber = substr($responseParts[50], -4);
        $card->expiryDate = $expiryDate;
        $card->cardType = $responseParts[51];
        //This is just for historic purposes and to allow the muck easy access
        DB::table('billing_paymentprofiles')->insert([
            'profileid' => $profile->getCustomerProfileId(),
            'paymentid' => $response->getCustomerPaymentProfileId(),
            'firstname' => '',
            'lastname' => '',
            'cardtype' => $card->cardType,
            'maskedcardnum' => $card->cardNumber,
            'expdate' => $card->expiryDate
        ]);
        $newPaymentProfileId = DB::table('billing_paymentprofiles')->where([
            'profileid' => $profile->getCustomerProfileId(),
            'paymentid' => $response->getCustomerPaymentProfileId()
        ])->value('id');
        DB::table('billing_profiles')->where([
            'profileid' => $profile->getCustomerProfileId()
        ])->update([
            'defaultcard' => $newPaymentProfileId
        ]);
        return $card;
    }

    /**
     * @inheritDoc
     */
    public function deleteCardFor(User $user, Card $card): void
    {
        $profile = $this->loadProfileFor($user);
        if (!$profile) throw new \Error("No valid profile found.");
        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        $request->setCustomerProfileId($profile->getCustomerProfileId());
        $request->setCustomerPaymentProfileId($card->id);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);
        if (!$response || $response->getMessages()->getResultCode() != "Ok") {
            $errorMessages = $response->getMessages()->getMessage();
            throw new \Exception("Couldn't create a payment profile. Response : "
                . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
        }
        //This is just for historic purposes and to allow the muck easy access
        DB::table('billing_paymentprofiles')->where([
            'profileid' => $profile->getCustomerProfileId(),
            'paymentid' => $card->id
        ])->delete();
    }

    /**
     * @inheritDoc
     */
    public function setDefaultCardFor(User $user, Card $card): void
    {
        $profile = $this->loadOrCreateProfileFor($user);
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($card->cardNumber);
        $creditCard->setExpirationDate($card->expiryDate);

        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);
        $paymentProfile = new AnetAPI\CustomerPaymentProfileExType();
        // $paymentprofile->setBillTo($billto);
        $paymentProfile->setPayment($paymentCreditCard);
        $paymentProfile->setCustomerPaymentProfileId($card->id);
        $paymentProfile->setDefaultPaymentProfile(true);
        $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        $request->setCustomerProfileId($profile->getCustomerProfileId());
        $request->setPaymentProfile($paymentProfile);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);
        if (!$response || $response->getMessages()->getResultCode() != "Ok") {
            $errorMessages = $response->getMessages()->getMessage();
            throw new \Exception("Couldn't update default payment profile. Response : "
                . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
        }
        //This is just for historic purposes and to allow the muck easy access
        $newPaymentProfileId = DB::table('billing_paymentprofiles')->where([
            'paymentid' => $card->id
        ])->value('id');
        DB::table('billing_profiles')->where([
            'profileid' => $profile->getCustomerProfileId()
        ])->update([
            'defaultcard' => $newPaymentProfileId
        ]);
    }

    /**
     * @inheritDoc
     */
    public function chargeCardFor(User $user, Card $card, float $amountToChargeUsd): string
    {
        $profile = $this->loadProfileFor($user);
        if (!$profile) throw new \Error("No valid profile found.");
        $transactionPaymentProfile = new AnetAPI\PaymentProfileType();
        $transactionPaymentProfile->setPaymentProfileId($card->id);

        $transactionCustomerProfile = new AnetAPI\CustomerProfilePaymentType();
        $transactionCustomerProfile->setCustomerProfileId($profile->getCustomerProfileId());
        $transactionCustomerProfile->setPaymentProfile($transactionPaymentProfile);

        $transaction = new AnetAPI\TransactionRequestType();
        $transaction->setTransactionType( "authCaptureTransaction");
        $transaction->setAmount($amountToChargeUsd);
        $transaction->setProfile($transactionCustomerProfile);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        $request->setRefId($this->refId());
        $request->setTransactionRequest($transaction);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);

        if (!$response || $response->getMessages()->getResultCode() != "Ok") {
            throw new \Exception("Error with transaction request: " .
                $response->getMessages()->getMessage()[0]->getCode() . ":" .
                $response->getMessages()->getMessage()[0]->getText()
            );
        }

        $transactionResponse = $response->getTransactionResponse();

        if (!$transactionResponse || $transactionResponse->getErrors()) {
            throw new \Exception("Error with transaction: " .
                $transactionResponse->getErrors()[0]->getErrorCode() . ":" .
                $transactionResponse->getErrors()[0]->getErrorText()
            );
        }

        return $transactionResponse->getTransId();
    }

    public function getDefaultCardFor(User $user): ?Card
    {
        $profile = $this->loadProfileFor($user);
        return $profile ? $profile->getDefaultCard()  : null;
    }

    public function getCardFor(User $user, int $cardId): ?Card
    {
        $profile = $this->loadProfileFor($user);
        return $profile ? $profile->getCard($cardId) : null;
    }

    public function getCardsFor(User $user): array
    {
        $profile = $this->loadProfileFor($user);
        return $profile ? $profile->getCards() : [];
    }

    public function getCustomerIdFor(User $user)
    {
        $profile = $this->loadProfileFor($user);
        return $profile ? $profile->getCustomerProfileId() : null;
    }
}
