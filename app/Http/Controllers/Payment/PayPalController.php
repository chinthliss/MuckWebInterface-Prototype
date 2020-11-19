<?php


namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPalManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    #region PayPal responses
    public function paypalOrderReturn(Request $request, PayPalManager $paypalManager,
                                      PaymentTransactionManager $transactionManager)
    {
        Log::debug("Paypal - order return: " . json_encode($request->all()));
        $token = $request->get('token');
        $transaction = $transactionManager->getTransactionFromExternalId($token);
        if (!$transaction || !$transaction->vendorTransactionId) {
            Log::error("PayPal - Told order " . $token . " has been accepted " .
                ", but either failed to look it up or looked up row is missing vendor_transaction_id.");
            abort(500);
        }
        if (!$transaction->open()) {
            Log::warning("Attempt to reclaim PayPal transaction " . $transaction->vendorTransactionId .
                " (User may have just pressed refresh at a bad time.)");
        } else {
            if (!$transaction->paid()) {
                $paypalManager->completePayPalOrder($transaction);
            }
            if ($transaction->paid()) {
                $transactionManager->fulfillTransaction($transaction);
                $transactionManager->closeTransaction($transaction, 'fulfilled');
            } else
                $transactionManager->closeTransaction($transaction, 'vendor_refused');
        }
        return redirect()->route('accountcurrency.transaction', ['id' => $transaction->id]);
    }

    public function paypalOrderCancel(Request $request, PayPalManager $paypalManager,
                                      PaymentTransactionManager $transactionManager)
    {
        Log::debug("Paypal - order cancel: " . json_encode($request->all()));
        $transaction = $transactionManager->getTransactionFromExternalId($request->get('token'));
        if (!$transaction->open()) return 403;
        $paypalManager->cancelPayPalOrder($transaction);
        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    public function paypalSubscriptionReturn(Request $request, PayPalManager $paypalManager,
                                             PaymentSubscriptionManager $subscriptionManager)
    {
        Log::debug("Paypal - subscription return: " . json_encode($request->all()));
        // Subscription returns subscription_id, ba_token and token
        $subscriptionVendorId = $request->get('subscription_id');
        $subscription = $subscriptionManager->getSubscriptionFromVendorId($subscriptionVendorId);
        if (!$subscription) {
            Log::error("PayPal - Told subscription " . $subscriptionVendorId . " has been accepted " .
                ", but failed to look it up");
            abort(500);
        }
        if ($subscription->status != 'approval_pending') {
            Log::warning("Attempt to approve PayPal subscription that isn't in approval_pending " . $subscription->id .
                " (User may have just pressed refresh at a bad time.)");
        } else {
            $paypalDetails = $paypalManager->getSubscriptionDetails($subscriptionVendorId);
            if ($paypalDetails['status'] == 'ACTIVE') {
                $subscriptionManager->updateVendorProfileId($subscription, $paypalDetails['subscriber']->payer_id);
                $subscriptionManager->setSubscriptionAsActive($subscription);
            }
        }
        return redirect()->route('accountcurrency.subscription', ['id' => $subscription->id]);
    }

    public function paypalSubscriptionCancel(Request $request, PayPalManager $paypalManager,
                                             PaymentSubscriptionManager $subscriptionManager)
    {
        Log::debug("Paypal - subscription cancel: " . json_encode($request->all()));
        $subscription = $subscriptionManager->getSubscriptionFromVendorId($request->get('subscription_id'));
        if (!$subscription->open()) return 403;
        $subscriptionManager->closeSubscription($subscription, 'cancelled');
        return redirect()->route('accountcurrency.subscription', ['id' => $subscription->id]);
    }

    public function paypalWebhook(Request $request, PayPalManager $paypalManager,
                                  PaymentTransactionManager $transactionManager)
    {
        $eventType = $request->get('event_type');
        Log::debug('Paypal Webhook ' . $eventType . ': ' . json_encode($request->all()));
        $verified = $paypalManager->verifyWebhookIsFromPayPal($request);
        if (!$verified) {
            Log::warning('Call from a PayPal Webhook could not be verified: ' . $request);
            return abort(401, 'Unverified');
        }
        switch($eventType) {
            case 'PAYMENT.SALE.COMPLETED':
                Log::debug('TODO: PayPal Webhook Implementation');
                break;
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
            Log::debug('TODO: PayPal Webhook Implementation');
                break;
            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                Log::debug('TODO: PayPal Webhook Implementation');
                break;
            default:
                Log::debug('No code to run for Paypal webhook: ' . $eventType);
                break;
        }
        return response('OK', 200);
    }

    #endregion PayPal responses

}
