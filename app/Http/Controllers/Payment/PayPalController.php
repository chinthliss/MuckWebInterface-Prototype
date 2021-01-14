<?php


namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPalManager;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    #region PayPal responses
    public function paypalOrderReturn(Request $request, PayPalManager $paypalManager,
                                      PaymentTransactionManager $transactionManager)
    {
        Log::debug("Paypal - order return: " . json_encode($request->all()));
        $token = $request->input('token');
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
        $transaction = $transactionManager->getTransactionFromExternalId($request->input('token'));
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
        $subscriptionVendorId = $request->input('subscription_id');
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
        $subscription = $subscriptionManager->getSubscriptionFromVendorId($request->input('subscription_id'));
        if (!$subscription->open()) return 403;
        $subscriptionManager->closeSubscription($subscription, 'cancelled');
        return redirect()->route('accountcurrency.subscription', ['id' => $subscription->id]);
    }

    public function paypalWebhook(Request $request, PayPalManager $paypalManager)
    {
        $eventType = $request->input('event_type');
        Log::debug('Paypal Webhook ' . $eventType . ': ' . json_encode($request->all()));
        $verified = $paypalManager->verifyWebhookIsFromPayPal($request);
        if (!$verified) {
            Log::warning('Call from a PayPal Webhook could not be verified: ' . $request);
            abort(401, 'Unverified');
        }
        // Setup subscription if provided
        $subscription = null;
        $subscriptionManager = null;
        // On subscription events:
        if ($request->input('resource_type') == 'subscription' && $request->input("resource.id")) {
            $subscriptionId = $request->input("resource.id");
            $subscriptionManager = resolve(PaymentSubscriptionManager::class);
            $subscription = $subscriptionManager->getSubscriptionFromVendorId($subscriptionId);
            if (!$subscription) throw new Error('Paypal webhook ' . $eventType . ' refers to unknown subscription: ' . $subscriptionId);
        }
        // On payment events that relate to a subscription:
        if ($request->input('resource.billing_agreement_id')) {
            $subscriptionId = $request->input("resource.billing_agreement_id");
            $subscriptionManager = resolve(PaymentSubscriptionManager::class);
            $subscription = $subscriptionManager->getSubscriptionFromVendorId($subscriptionId);
            //This might occur with old subscriptions?
            if (!$subscription) throw new Error('Paypal webhook ' . $eventType . ' refers to unknown subscription: ' . $subscriptionId);
        }
        if ($subscription) Log::debug('Using subscription ' . json_encode($subscription));

        switch ($eventType) {

            case 'PAYMENT.CAPTURE.PENDING':
            case 'PAYMENT.SALE.PENDING':
                //Shouldn't see these unless something didn't auto-capture
                //Happened in local dev due to the sandbox business account not being set to use USD
                Log::warning('Paypal Webhook ' . $eventType . ' notified us of a payment that went to pending:'
                    . ': ' . json_encode($request->all()));
                break;


            case 'PAYMENT.CAPTURE.COMPLETED':
                //Nothing to do, as we already know from the response
                break;

            case 'PAYMENT.SALE.COMPLETED':
                if ($subscription) {
                    $amount = $request->input('resource.amount.total');
                    $vendorTransactionId = $request->input('resource.id');
                    //Need a transaction to represent the payment that's already occurred
                    $transactionManager = resolve(PaymentTransactionManager::class);
                    $transaction = $transactionManager->getTransactionFromExternalId($vendorTransactionId);
                    if (!$transaction) {
                        $transaction = $subscriptionManager->createTransactionForSubscription($subscription);
                        $transactionManager->updateVendorTransactionId($transaction, $vendorTransactionId);
                        $transactionManager->setPaid($transaction);
                    }
                    $subscriptionManager->processPaidSubscriptionTransaction($subscription, $transaction);
                } else {
                    Log::info("Paypal Webhook - no subscription found to pay against.");
                }
                break;

            case 'BILLING.SUBSCRIPTION.CREATED':
                //We made it, so don't need to react
                break;

            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                //For an initial subscription we should already know but this might be used be continuations
                //Witnessed this arriving AFTER a payment so not reliable for order
                $subscriptionManager->setSubscriptionAsActive($subscription);
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $subscriptionManager->closeSubscription($subscription, 'cancelled');
                break;

            case 'BILLING.SUBSCRIPTION.EXPIRED':
                $subscriptionManager->closeSubscription($subscription, 'expired');
                break;

            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $subscriptionManager->suspendSubscription($subscription);
                break;

            case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
                Log::debug('TODO: PayPal Webhook Implementation - failed payment');
                break;

            default:
                Log::debug('No code to run for Paypal webhook: ' . $eventType);
                break;
        }
        return response('OK', 200);
    }

    #endregion PayPal responses

}
