<?php


namespace App\Payment;

use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use \Error;
use \Exception;
use \InvalidArgumentException;

class AuthorizeNetCardPaymentManager implements CardPaymentManager
{

    private $loginId = '';
    private $transactionKey = '';
    private $endPoint = '';

    private function transactionManager() : PaymentTransactionManager
    {
        return resolve(PaymentTransactionManager::class);
    }

    private function subscriptionManager() : PaymentSubscriptionManager
    {
        return resolve(PaymentSubscriptionManager::class);
    }

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
            throw new Error('Configuration for Authorize.Net is missing loginId or transactionKey');
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
            Log::debug('AuthorizeNet - Requesting profile#' . $profileId . ' for AID#' . $user->getAid());
            $request = new AnetAPI\GetCustomerProfileRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication());
            $request->setCustomerProfileId($profileId);
            $request->setUnmaskExpirationDate(true);
            $controller = new AnetController\GetCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->endPoint);
            if ($response && $response->getMessages()->getResultCode() == "Ok") {
                $profile = AuthorizeNetCardPaymentCustomerProfile::fromApiResponse($response);
                if ($profile->getMerchantCustomerId() != $accountId) {
                    // $profile = null;
                    Log::warning("Retrieved Authorize.net customer profile for AID " . $accountId . " didn't have a matching merchantId.");
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
                $this->customerProfiles[$accountId] = $profile;
            }
            else {
                $message = $response->getMessages()->getMessage()[0];
                Log::error('Failed to request Authorize.net profile#' . $profileId . ', Response=' .
                    $message->getCode() . ':' .$message->getText()
                );
            }
        }
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
            Log::debug('AuthorizeNet - Creating profile for User#' . $user->getAid());
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
                //Check if it's a case if the merchant says there's already an entry for this person.
                preg_match(
                    "/A duplicate record with ID (\d+) already exists./",
                    $errorMessages[0]->getText(), $attemptToFindExistingId
                );
                if ($attemptToFindExistingId && $attemptToFindExistingId[1]) {
                    LOG::warning('We have no valid Authorize.Net account for User#' . $user->getAid() .
                        ' but AN reported one with an ID of ' . $attemptToFindExistingId[1] .
                        ' (Attempting to repair our record and use this ID.)'
                    );
                    DB::table('billing_profiles')->updateOrInsert([
                        'aid' => $user->getAid()
                    ],[
                        'profileid' => $attemptToFindExistingId[1],
                        'defaultcard' => 0,
                        'spendinglimit' => 0
                    ]);
                    //Once more try to load it
                    $profile = $this->loadProfileFor($user);
                }
                if (!$profile) throw new Exception("Couldn't create a profile. Response = "
                    . $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText() . "\n");
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
    public function createCardFor(User $user, string $cardNumber,
                                  string $expiryDate, string $securityCode): Card
    {
        $profile = $this->loadOrCreateProfileFor($user);
        Log::debug('AuthorizeNet - Registering a new card for User#' . $user->getAid());
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
                throw new InvalidArgumentException("The transaction was unsuccessful.");
            } else
                throw new Exception("Couldn't create a payment profile. Response : "
                    . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
        }
        $card = new Card();
        $card->id = $response->getCustomerPaymentProfileId();
        //Silly that this has to be extracted from a huge comma separated string..
        $responseParts = explode(',', $response->getValidationDirectResponse());
        $card->cardNumber = substr($responseParts[50], -4);
        // $expiryDate is in the form MM/YYYY
        $parts = explode('/', $expiryDate);
        $card->expiryDate = Carbon::createFromDate($parts[1], $parts[0], 1);
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
        Log::debug('AuthorizeNet - New card registered as PaymentProfile#' . $card->id);
        return $card;
    }

    /**
     * @inheritDoc
     */
    public function deleteCardFor(User $user, Card $card): void
    {
        $profile = $this->loadProfileFor($user);
        if (!$profile) throw new Error("No valid profile found.");
        Log::debug('AuthorizeNet - Deleting card with PaymentProfile#' . $card->id);
        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        $request->setCustomerProfileId($profile->getCustomerProfileId());
        $request->setCustomerPaymentProfileId($card->id);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);
        if (!$response || $response->getMessages()->getResultCode() != "Ok") {
            $errorMessages = $response->getMessages()->getMessage();
            throw new Exception("Couldn't create a payment profile. Response : "
                . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n");
        }
        $profile->removeCard($card);
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
            throw new Exception("Couldn't update default payment profile. Response : "
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
    public function chargeCardFor(User $user, Card $card, PaymentTransaction $transaction)
    {
        $profile = $this->loadProfileFor($user);
        if (!$profile) throw new Error("No valid profile found.");
        Log::debug('AuthorizeNet - Charging card with PaymentProfile#' . $card->id);
        $transactionPaymentProfile = new AnetAPI\PaymentProfileType();
        $transactionPaymentProfile->setPaymentProfileId($card->id);

        $transactionCustomerProfile = new AnetAPI\CustomerProfilePaymentType();
        $transactionCustomerProfile->setCustomerProfileId($profile->getCustomerProfileId());
        $transactionCustomerProfile->setPaymentProfile($transactionPaymentProfile);

        $anetTransaction = new AnetAPI\TransactionRequestType();
        $anetTransaction->setTransactionType( "authCaptureTransaction");
        $anetTransaction->setAmount($transaction->totalPriceUsd());
        $anetTransaction->setProfile($transactionCustomerProfile);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication());
        $request->setRefId($this->refId());
        $request->setTransactionRequest($anetTransaction);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse($this->endPoint);

        if (!$response || $response->getMessages()->getResultCode() != "Ok") {
            throw new Exception("Error with transaction request: " .
                $response->getMessages()->getMessage()[0]->getCode() . ":" .
                $response->getMessages()->getMessage()[0]->getText()
            );
        }

        $transactionResponse = $response->getTransactionResponse();

        if (!$transactionResponse || $transactionResponse->getErrors()) {
            throw new Exception("Error with AuthorizeNet transaction: " .
                $transactionResponse->getErrors()[0]->getErrorCode() . ":" .
                $transactionResponse->getErrors()[0]->getErrorText()
            );
        }
        $transactionManager = $this->transactionManager();
        $transactionManager->updateVendorTransactionId($transaction, $transactionResponse->getTransId());
        $transactionManager->setPaid($transaction);
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
