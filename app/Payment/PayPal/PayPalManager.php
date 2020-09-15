<?php


namespace App\Payment\PayPal;

use App\Payment\PaymentTransaction;
use App\User;
use App\Payment\PaymentTransactionManager;
use Illuminate\Support\Facades\Log;
use PayPalCheckoutSdk\Core\PayPalEnvironment;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;

class PayPalManager
{

    /**
     * @var PaymentTransactionManager
     */
    private $transactionManager;

    /**
     * @var PayPalHttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $account;

    /**
     * Product ID used to create subscriptions for
     * @var string
     */
    private $subscriptionId;

    public function __construct(string $account, PayPalEnvironment $environment,
                                PaymentTransactionManager $transactionManager,
                                string $subscriptionId)
    {
        $this->account = $account;
        $this->client = new PayPalHttpClient($environment);
        $this->transactionManager = $transactionManager;
        $this->$subscriptionId = $subscriptionId;
    }

    public function startPayPalOrderFor(User $user, PaymentTransaction $transaction)
    {
        Log::debug("Paypal - creating order for transaction#" . $transaction->id);
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => $transaction->id,
                "amount" => [
                    "value" => $transaction->totalPriceUsd(),
                    "currency_code" => "USD"
                ]
            ]],
            "application_context" => [
                "cancel_url" => route('accountcurrency.paypal.cancel'),
                "return_url" => route('accountcurrency.paypal.return')
            ]
        ];

        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create payment got the following response: " .
                json_encode($ex));
        }
        $this->transactionManager->updateExternalId($transaction, $response->result->id);
        Log::debug("Paypal - created order for transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->externalId);
        // Response contains an array of links in the form {href, rel, method}.
        // We need to find the one where rel=approve
        foreach ($response->result->links as $link) {
            if ($link->rel == 'approve') return $link->href;
        }
        throw new \Exception("No approve link given in response from PayPal.");
    }

    public function cancelPayPalOrder(PaymentTransaction $transaction)
    {
        $this->transactionManager->closeTransaction($transaction, 'user_declined');
    }

    public function completePayPalOrder(PaymentTransaction $transaction): bool
    {
        Log::debug("Paypal - capturing transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->externalId);
        $request = new OrdersCaptureRequest($transaction->externalId);
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to complete payment got the following response: " .
                json_encode($ex));
            return false;
        }
        $this->transactionManager->updatePaymentProfileId($transaction, $response->result->payer->payer_id);
        Log::debug("Paypal - captured transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->externalId . " for PayPalProfile#" . $transaction->paymentProfileId);
        return ($response->result->status == 'COMPLETED');
    }

    public function getSubscriptionPlans(): array
    {
        Log::debug("Paypal - Getting subscription plans");
        $request = new SubscriptionsListPlans();
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get subscription plans got the following response: " .
                json_encode($ex));
            return [];
        }
        //TODO - Filter plans to only return appropriate ones for this application
        return $response->result->plans;
    }

    public function getSubscriptionPlan(string $frequencyDays)
    {
        $planId = null;
        //TODO - write getSubscriptionPlanFor's retrival

        if (!$planId) { //Need to create one
            //TODO - Validate product exists
            Log::debug("Paypal - creating subscription plan for " . $frequencyDays . " days for product "
                . $this->subscriptionId);
            $request = new SubscriptionsCreatePlan();
            $request->prefer('return=representation');
            $request->body = [
                "product_id" => $this->subscriptionId ,
                "name" => config('app.name') . " subscription plan, every " . $frequencyDays . ' days',
                "billing_cycle" => [[
                    "tenure_type" => "REGULAR",
                    "total_cycles" => 0,
                    "frequency" => [
                        "interval_unit" => "DAY",
                        "interval_count" => $frequencyDays
                    ],
                    "pricing_scheme" => [
                        "fixed_price" => [
                            "currency_code" => "USD",
                            "value" => 1
                        ]
                    ]
                ]]
            ];
            try {
                $response = $this->client->execute($request);
            } catch (HttpException $ex) {
                Log::error("Paypal - attempt to create subscription plan got the following response: " .
                    json_encode($ex));
                return null;
            }
            $planId = $response->result->id;
        }
        return $planId;
    }

    public function getProducts(): array
    {
        $request = new ProductsList();
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get products got the following response: " .
                json_encode($ex));
            return [];
        }
        return $response->result->products;
    }

    public function getWebhooks(): array
    {
        $request = new WebhooksList();
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get webhooks got the following response: " .
                json_encode($ex));
            return [];
        }
        return $response->result->webhooks;
    }


}
