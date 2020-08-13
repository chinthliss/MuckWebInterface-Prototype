<?php


namespace App\Payment;

use App\User;
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

    public function __construct(string $account, PayPalEnvironment $environment,
                                PaymentTransactionManager $transactionManager)
    {

        $this->account = $account;
        $this->client = new PayPalHttpClient($environment);
        $this->transactionManager = $transactionManager;
    }

    public function startPayPalOrderFor(User $user, PaymentTransaction $transaction)
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => $transaction->id,
                "amount" => [
                    "value" => $transaction->accountCurrencyPriceUsd,
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
            $this->transactionManager->updateExternalId($transaction, $response->result->id);
            // Response contains an array of links in the form {href, rel, method}.
            // We need to find the one where rel=approve
            foreach ($response->result->links as $link) {
                if ($link->rel == 'approve') return $link->href;
            }
            throw new \Exception("No approve link given in response from PayPal.");
        } catch (HttpException $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function cancelPayPalOrder(PaymentTransaction $transaction)
    {
        $this->transactionManager->closeTransaction($transaction, 'user_declined');
    }

    public function completePayPalOrder(PaymentTransaction $transaction): bool
    {
        $request = new OrdersCaptureRequest($transaction->externalId);
        try {
            $response = $this->client->execute($request);
            $this->transactionManager->updatePaymentProfileId($transaction, $response->result->payer->payer_id);
            return ($response->result->status == 'COMPLETED');
        } catch (HttpException $ex) {
            Log::error("Paypal - attempt to complete payment got the following error from PayPal: " .
                json_encode($ex));
            return false;
        }
    }
}
