<?php


namespace App\Payment;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\PayPalEnvironment;

class PayPalManager
{

    private $account;

    private $clientId;

    private $secret;

    /**
     * @var PayPalEnvironment
     */
    private $environment;

    /**
     * @var PayPalHttpClient
     */
    private $client;

    public function __construct(PayPalEnvironment $environment, $account, $clientId, $secret)
    {
        $this->account = $account;
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->environment = $environment;
        $this->client = new PayPalHttpClient($this->environment);
    }
}
