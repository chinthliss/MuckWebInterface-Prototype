<?php


namespace App\Payment;

use App\Notifications\PaymentTransactionPaid;
use App\User;
use App\Payment\PayPalRequests\ProductsCreate;
use App\Payment\PayPalRequests\ProductsList;
use App\Payment\PayPalRequests\SubscriptionsCreatePlan;
use App\Payment\PayPalRequests\SubscriptionsDetails;
use App\Payment\PayPalRequests\SubscriptionsCancelSubscription;
use App\Payment\PayPalRequests\SubscriptionsCreateSubscription;
use App\Payment\PayPalRequests\SubscriptionsListPlans;
use App\Payment\PayPalRequests\WebhooksCreate;
use App\Payment\PayPalRequests\WebhooksList;
use App\Payment\PayPalRequests\WebhooksVerifySignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use PayPalCheckoutSdk\Core\PayPalEnvironment;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;
use \Exception;

class PayPalManager
{

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

    private $subscriptionPlans;

    public function __construct(string $account, PayPalEnvironment $environment,
                                string $subscriptionId)
    {
        $this->account = $account;
        $this->client = new PayPalHttpClient($environment);
        $this->subscriptionId = $subscriptionId;
    }

    private function transactionManager(): PaymentTransactionManager
    {
        return resolve(PaymentTransactionManager::class);
    }

    private function subscriptionManager(): PaymentSubscriptionManager
    {
        return resolve(PaymentSubscriptionManager::class);
    }

    #region Order functionality
    public function startPayPalOrderFor(User $user, PaymentTransaction $transaction): string
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
                "shipping_preference" => "NO_SHIPPING",
                "cancel_url" => route('accountcurrency.paypal.order.cancel'),
                "return_url" => route('accountcurrency.paypal.order.return')
            ]
        ];

        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create payment got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            throw new \Exception("There was an issue with the request to PayPal.");
        }
        $this->transactionManager()->updateVendorTransactionId($transaction, $response->result->id);
        Log::debug("Paypal - created order for transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->vendorTransactionId);
        // Response contains an array of links in the form {href, rel, method}.
        // We need to find the one where rel=approve
        foreach ($response->result->links as $link) {
            if ($link->rel == 'approve') return $link->href;
        }
        throw new \Exception("No approve link given in response from PayPal.");
    }

    public function cancelPayPalOrder(PaymentTransaction $transaction)
    {
        $this->transactionManager()->closeTransaction($transaction, 'user_declined');
    }

    /**
     * @param PaymentTransaction $transaction
     */
    public function completePayPalOrder(PaymentTransaction $transaction)
    {
        Log::debug("Paypal - capturing transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->vendorTransactionId);
        $request = new OrdersCaptureRequest($transaction->vendorTransactionId);
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to complete payment got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            throw new Exception("Attempt to complete payment with Paypal failed.");
        }
        //With PayPal we only discover the profile ID of the customer AFTER they accept
        $transactionManager = $this->transactionManager();
        $transactionManager->updateVendorProfileId($transaction, $response->result->payer->payer_id);
        Log::debug("Paypal - captured transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->vendorTransactionId
            . " for PayPalProfile#" . $transaction->vendorProfileId
            . ": " . $response->result->status);
        if ($response->result->status == 'COMPLETED') {
            $transactionManager->setPaid($transaction);
            User::find($transaction->accountId)->notify(new PaymentTransactionPaid($transaction));
        }
    }
    #endregion Order functionality

    #region Subscription functionality
    public function startPayPalSubscriptionFor(User $user, PaymentSubscription $subscription)
    {
        Log::debug("Paypal - creating subscription request for subscription " . $subscription->id);

        $request = new SubscriptionsCreateSubscription();
        $request->prefer('return=representation');
        $request->body = [
            "plan_id" => $subscription->vendorSubscriptionPlanId,
            "quantity" => (int)$subscription->amountUsd,
            "application_context" => [
                "shipping_preference" => "NO_SHIPPING",
                "cancel_url" => route('accountcurrency.paypal.subscription.cancel'),
                "return_url" => route('accountcurrency.paypal.subscription.return')
            ]
        ];
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create payment got the following response: "
                . "(" . $ex->statusCode . ") " . $ex->getMessage());
            throw new \Exception("There was an issue with the request to PayPal.");
        }

        $this->subscriptionManager()->updateVendorSubscriptionId($subscription, $response->result->id);
        Log::debug("Paypal - created subscription for subscription#" . $subscription->id
            . ", PayPalId#" . $subscription->vendorSubscriptionId);
        // Response contains an array of links in the form {href, rel, method}.
        // We need to find the one where rel=approve
        foreach ($response->result->links as $link) {
            if ($link->rel == 'approve') return $link->href;
        }
        throw new \Exception("No approve link given in response from PayPal.");

    }

    public function cancelSubscription(PaymentSubscription $subscription)
    {
        Log::debug("Paypal - Cancelling subscription " . $subscription->id
            . ", PayPalID=" . $subscription->vendorSubscriptionId);

        // Request details on the subscription first to make sure it exists and isn't already cancelled
        $paypalSubscription = null;
        try {
            $paypalSubscription = $this->getSubscriptionDetails($subscription->vendorSubscriptionId);
        } catch (HttpException $ex) {
            Log::warning("Paypal - Cancel Subscription - Querying subscription "
                . $subscription->vendorSubscriptionId . " got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
        }

        if ($paypalSubscription && $paypalSubscription["status"] !== 'CANCELLED') {
            $request = new SubscriptionsCancelSubscription($subscription->vendorSubscriptionId);
            $request->prefer('return=representation');

            $request->body = [
                "reason" => "Cancelled"
            ];
            log::debug('Paypal - Cancel Subscription request:' . json_encode($request->body));

            try {
                $response = $this->client->execute($request);
            } catch (HttpException $ex) {
                Log::error("Paypal - attempt to cancel subscription " . $subscription->vendorSubscriptionId
                    . " got the following response: "
                    . "(" . $ex->statusCode . ") " . $ex->getMessage());
                throw new \Exception("There was an issue with the request to PayPal.");
            }
        } else {
            Log::warning("Paypal - Cancel Subscription - Subscription doesn't exist or is already cancelled");
        }
    }

    public function getSubscriptionDetails(string $paypalSubscriptionId): array
    {
        Log::debug("Paypal - looking up subscription details for PayPalId#" . $paypalSubscriptionId);

        $request = new SubscriptionsDetails($paypalSubscriptionId);
        $request->prefer('return=representation');
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get subscription details got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            throw new \Exception("There was an issue with the request to PayPal.");
        }
        return (array)$response->result;
    }

    #endregion Subscription functionality

    public function verifyWebhookIsFromPayPal(Request $webhookRequest): bool
    {
        Log::debug("Paypal - Requesting webhook call verification.");
        $webhooks = $this->getWebhooks();
        $webhookId = null;
        foreach ($webhooks as $webhook) {
            if (strpos($webhook->url, $webhookRequest->path()) !== false) $webhookId = $webhook->id;
        }
        if (!$webhookId) {
            Log::error("Paypal - Could not lookup a webhookID for webhook using " . $webhookRequest->path());
        }
        $request = new WebhooksVerifySignature();
        $request->body = [
            'auth_algo' => $webhookRequest->header('Paypal-Auth-Algo'),
            'cert_url' => $webhookRequest->header('Paypal-Cert-Url'),
            'transmission_id' => $webhookRequest->header('Paypal-Transmission-Id'),
            'transmission_sig' => $webhookRequest->header('Paypal-Transmission-Sig'),
            'transmission_time' => $webhookRequest->header('Paypal-Transmission-Time'),
            'webhook_id' => $webhookId,
            'webhook_event' => $webhookRequest->json()
        ];
        log::debug('Paypal Webhook Verification request:' . json_encode($request->body));
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to verify webhook call got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return false;
        }
        log::debug('Paypal Webhook Verification response:' . json_encode($response));

        //Needs to be successful unless in dev because the webhook simulated calls can't be verified
        return ($response->statusCode == 200 &&
            ($response->result->verification_status == 'SUCCESS' || App::environment(['local', 'development'])));
    }

    #region Configuration functionality
    public function getSubscriptionPlans(): array
    {
        if ($this->subscriptionPlans) return $this->subscriptionPlans;

        Log::debug("Paypal - Getting subscription plans");
        $this->subscriptionPlans = [];
        $pageNumber = 1;
        $totalPages = 1;
        while ($pageNumber <= $totalPages) {
            $request = new SubscriptionsListPlans();
            $request->path .= http_build_query([
                'total_required' => 'true',
                'product_id' => $this->subscriptionId,
                'page' => $pageNumber
            ]);
            $request->prefer('return=representation');
            try {
                $response = $this->client->execute($request);
            } catch (HttpException $ex) {
                Log::error("Paypal - attempt to get subscription plans got the following response: " .
                    "(" . $ex->statusCode . ") " . $ex->getMessage());
                return [];
            }
            foreach ($response->result->plans as $plan) {
                $this->subscriptionPlans[$plan->id] = $plan;
            }
            $pageNumber++;
            $totalPages = $response->result->total_pages;
        }
        return $this->subscriptionPlans;
    }

    public function getSubscriptionPlan(string $frequencyDays)
    {
        $plans = $this->getSubscriptionPlans();
        foreach ($plans as $plan) {
            if ($plan->status != 'ACTIVE') continue;
            if (!$plan->quantity_supported) continue;
            foreach ($plan->billing_cycles as $cycle) {
                if ($cycle->tenure_type == 'REGULAR' && $cycle->frequency->interval_count == $frequencyDays)
                    return $plan->id;
            }
        }
        return null;
    }

    public function createSubscriptionPlan(int $frequencyDays): ?string
    {
        Log::debug("Paypal - creating subscription plan for " . $frequencyDays . " days for product "
            . $this->subscriptionId);
        $request = new SubscriptionsCreatePlan();
        $request->body = [
            "product_id" => $this->subscriptionId,
            "name" => config('app.name') . " subscription plan, every " . $frequencyDays . ' days',
            "billing_cycles" => [[
                "tenure_type" => "REGULAR",
                "sequence" => 1,
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
            ]],
            "quantity_supported" => true,
            "payment_preferences" => [
                "auto_bill_outstanding" => true,
            ]
        ];
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create subscription plan got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return null;
        }
        return $response->result->id;
    }

    public function getProducts(): array
    {
        Log::debug("Paypal - Getting products");
        $request = new ProductsList();
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get products got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return [];
        }
        $results = [];
        foreach ($response->result->products as $product) {
            $results[$product->id] = $product;
        }
        return $results;
    }

    public function getProduct(string $productId): ?string
    {
        $products = $this->getProducts();
        if (array_key_exists($productId, $products)) return $products[$productId];
        return null;
    }

    public function createProduct(string $productId, string $description): ?string
    {
        $request = new ProductsCreate();
        $request->body = [
            "id" => $productId,
            "name" => $description,
            "type" => "DIGITAL"
        ];
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create product got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return null;
        }
        return $response->result->id;
    }

    public function getWebhooks(): array
    {
        Log::debug("Paypal - Getting webhooks");
        $request = new WebhooksList();
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to get webhooks got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return [];
        }
        $webhooks = [];
        foreach ($response->result->webhooks as $webhook) {
            $webhooks[$webhook->id] = $webhook;
        }
        return $webhooks;
    }

    public function createWebhook(string $url, array $webhookTypes): ?string
    {

        $request = new WebhooksCreate();
        $parsedTypes = [];
        foreach ($webhookTypes as $type) {
            array_push($parsedTypes, [
                "name" => $type
            ]);
        }
        $request->body = [
            'url' => $url,
            'event_types' => $parsedTypes
        ];

        // Override - we request all event types so we can identify changes/undocumented ones.
        // Done because Paypal was returning a webhook (PAYMENT.SALE.PENDING) that was not listed in their ref docs
        $request->body['event_types'] = [['name' => '*']];

        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create webhook got the following response: " .
                "(" . $ex->statusCode . ") " . $ex->getMessage());
            return null;
        }
        return $response->result->id;
    }
    #endregion Configuration functionality

}
