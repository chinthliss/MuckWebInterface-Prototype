<?php


namespace App\Payment\PayPal;

use App\Payment\PaymentTransaction;
use App\User;
use App\Payment\PaymentTransactionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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

    private $subscriptionPlans;

    public function __construct(string $account, PayPalEnvironment $environment,
                                PaymentTransactionManager $transactionManager,
                                string $subscriptionId)
    {
        $this->account = $account;
        $this->client = new PayPalHttpClient($environment);
        $this->transactionManager = $transactionManager;
        $this->subscriptionId = $subscriptionId;
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
                $ex->getMessage());
        }
        $this->transactionManager->updateVendorTransactionId($transaction, $response->result->id);
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
        $this->transactionManager->closeTransaction($transaction, 'user_declined');
    }

    public function completePayPalOrder(PaymentTransaction $transaction): bool
    {
        Log::debug("Paypal - capturing transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->vendorTransactionId);
        $request = new OrdersCaptureRequest($transaction->vendorTransactionId);
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to complete payment got the following response: " .
                $ex->getMessage());
            return false;
        }
        $this->transactionManager->updateVendorProfileId($transaction, $response->result->payer->payer_id);
        Log::debug("Paypal - captured transaction#" . $transaction->id
            . ", PayPalId#" . $transaction->vendorTransactionId . " for PayPalProfile#" . $transaction->paymentProfileId);
        return ($response->result->status == 'COMPLETED');
    }

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
        log::info('Paypal Webhook Verification request:' . json_encode($request->body));
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to verify webhook call got the following response: " .
                $ex->getMessage());
            return false;
        }
        log::info('Paypal Webhook Verification response:' . json_encode($response));

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
                    $ex->getMessage());
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
                ],
                "quantity_supported" => true
            ]],
            "payment_preferences" => [
                "auto_bill_outstanding" => true,
            ]
        ];
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create subscription plan got the following response: " .
                $ex->getMessage());
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
                $ex->getMessage());
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
                $ex->getMessage());
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
                $ex->getMessage());
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
        try {
            $response = $this->client->execute($request);
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to create webhook got the following response: " .
                $ex->getMessage());
            return null;
        }
        return $response->result->id;
    }
    #endregion Configuration functionality

}
