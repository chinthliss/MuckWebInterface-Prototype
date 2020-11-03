<?php

namespace App\Http\Controllers\Payment;

use App\Payment\CardPaymentManager;
use App\Muck\MuckConnection;
use App\Http\Controllers\Controller;
use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransaction;
use App\Payment\PaymentTransactionItemCatalogue;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPalManager;
use App\User;
use Error;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountCurrencyController extends Controller
{
    private const minimumAmountUsd = 5;

    private const suggestedAmounts = [5, 10, 20, 50];

    public function show(CardPaymentManager $cardPaymentManager, MuckConnection $muck,
                         PaymentTransactionItemCatalogue $itemsCatalogue)
    {
        /** @var User $user */
        $user = auth()->user();

        $defaultCard = $cardPaymentManager->getDefaultCardFor($user);

        $parsedSuggestedAmounts = [];
        foreach (self::suggestedAmounts as $amount) {
            $parsedSuggestedAmounts[$amount] = $muck->usdToAccountCurrency($amount);
        }

        $parsedItems = [];
        foreach ($itemsCatalogue->getEligibleItemsFor($user) as $code) {
            array_push($parsedItems, $itemsCatalogue->itemCodeToArray($code));
        }

        return view('account-currency')->with([
            'account' => $user->getAid(),
            'defaultCardMaskedNumber' => ($defaultCard ? $defaultCard->maskedCardNumber() : null),
            'suggestedAmounts' => $parsedSuggestedAmounts,
            'itemCatalogue' => $parsedItems
        ]);
    }

    /**
     * @param Request{amount} $request
     * @param MuckConnection $muck
     * @return void|int;
     */
    public function usdToAccountCurrency(Request $request, MuckConnection $muck)
    {
        $amountUsd = $request->input('amount', 0);

        if (!is_numeric($amountUsd) || $amountUsd - floor($amountUsd) > 0.0)
            return abort(400, 'Whole numbers only');

        if (!$amountUsd || $amountUsd < self::minimumAmountUsd)
            return abort(400, 'Below minimum amount of $' . self::minimumAmountUsd);

        return $muck->usdToAccountCurrency($amountUsd);
    }

    //Returns {accountCurrencyUsd, items, recurringInterval}
    private function parseBaseRequest(Request $request): array
    {
        $amountUsd = (int)$request->input('amountUsd', 0);
        if ($amountUsd && $amountUsd < self::minimumAmountUsd)
            throw new Exception('Amount specified was beneath minimum amount of $' . self::minimumAmountUsd . '.');

        $items = $request->has('items') ? $request['items'] : [];
        if (!$items && !$amountUsd)
            throw new Exception("Transaction has no value.<br/>" .
                "You need to specify either an amount or select item(s).");

        $recurringInterval = $request->has('recurringInterval') ? (int)$request['recurringInterval'] : null;

        return [
            'accountCurrencyUsd' => $amountUsd,
            'items' => $items,
            'recurringInterval' => $recurringInterval
        ];
    }

    /**
     * @param Request $request
     * @param PaymentTransactionManager $transactionManager
     * @return array|ResponseFactory
     */
    public function newPayPalTransaction(Request $request, PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }

        if ($baseDetails['recurringInterval']) return response("A transaction can't have an interval.");

        return $transactionManager->createTransaction(
            $user, "paypal", "paypal_unattributed",
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items']
        )->toTransactionOfferArray();

    }

    /**
     * @param Request $request
     * @param CardPaymentManager $cardPaymentManager
     * @param PaymentTransactionManager $transactionManager
     * @return array|ResponseFactory
     */
    public function newCardTransaction(Request $request, CardPaymentManager $cardPaymentManager,
                                       PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $cardId = $request->input('cardId', null);
        $card = null;
        $card = $cardId ? $cardPaymentManager->getCardFor($user, $cardId)
            : $cardPaymentManager->getDefaultCardFor($user);
        if (!$card) return response("Default card couldn't be found or isn't valid.", 400);

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }

        if ($baseDetails['recurringInterval']) return response("A transaction can't have an interval.");

        return $transactionManager->createTransaction(
            $user, 'authorizenet', $card->id,
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items']
        )->toTransactionOfferArray();
    }


    /**
     * @param PaymentTransaction $transaction
     */
    private function fulfillTransaction(PaymentTransaction $transaction)
    {
        //Actual fulfilment is done by the MUCK still, due to ingame triggers
        $muck = resolve('App\Muck\MuckConnection');

        if ($transaction->accountCurrencyQuoted) {
            $transaction->accountCurrencyRewarded = $muck->adjustAccountCurrency(
                $transaction->accountId,
                $transaction->accountCurrencyPriceUsd,
                $transaction->accountCurrencyQuoted,
                ''
            );
        }

        if ($transaction->items) {
            $transaction->accountCurrencyRewardedForItems = 0;
            foreach ($transaction->items as $item) {
                $transaction->accountCurrencyRewardedForItems += $muck->rewardItem(
                    $transaction->accountId,
                    $item->priceUsd,
                    $item->accountCurrencyValue,
                    $item->code
                );
            }
        }
    }

    public function declineTransaction(Request $request, PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $transactionId = $request->input('token', null);

        if (!$transactionId || !$user) return abort(403);

        $transaction = $transactionManager->getTransaction($transactionId);

        if ($transaction->accountId != $user->getAid() || !$transaction->open()) return abort(403);

        $transactionManager->closeTransaction($transaction, 'user_declined');
        return "Transaction Declined";
    }

    public function acceptTransaction(Request $request, PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $transactionId = $request->input('token', null);

        if (!$transactionId || !$user) return abort(403);

        $transaction = $transactionManager->getTransaction($transactionId);

        if ($transaction->accountId != $user->getAid() || !$transaction->open()) return abort(403);

        // If this is a paypal transaction, we create an order with them and redirect user to their approval
        if ($transaction->vendor == 'paypal') {
            $payPalManager = resolve('App\Payment\PayPalManager');
            try {
                $approvalUrl = $payPalManager->startPayPalOrderFor($user, $transaction);
                return redirect($approvalUrl);
            } catch (Exception $e) {
                Log::info("Error during starting paypal payment: " . $e);
                return abort(500);
            }
        }

        //Otherwise we attempt to charge the card
        if (!$transaction->paid()) {
            if ($transaction->vendor !== 'paypal') {
                $cardPaymentManager = resolve('App\Payment\CardPaymentManager');
                $card = $cardPaymentManager->getCardFor($user, $transaction->vendorProfileId);
                try {
                    $cardPaymentManager->chargeCardFor($user, $card, $transaction);
                } catch (Exception $e) {
                    Log::info("Error during card payment: " . $e);
                }
            }
        }

        if ($transaction->paid()) {
            $this->fulfillTransaction($transaction);
            $transactionManager->closeTransaction($transaction, 'fulfilled');
        } else
            $transactionManager->closeTransaction($transaction, 'vendor_refused');
        return redirect()->route('accountcurrency.transaction', [
            'id' => $transactionId
        ]);
    }

    public function viewTransaction(PaymentTransactionManager $transactionManager, string $id)
    {
        // TODO: For later, from paypal docs: with PayerID and paymentId appended to the URL.

        /** @var User $user */
        $user = auth()->user();

        if (!$id) return abort(401);

        $transaction = $transactionManager->getTransaction($id);

        if ($transaction->accountId != $user->getAid()) return abort(403);

        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    public function viewTransactions(PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $accountToView = $user->getAid();

        if (!$accountToView) return abort(401);

        //TODO Leaving room to allow admin to view others
        if ($accountToView !== $user->getAid()) return abort(403);

        return view('account-currency-transactions')->with([
            'transactions' => $transactionManager->getTransactionsFor($accountToView)
        ]);
    }

    #region PayPal responses
    public function paypalReturn(Request $request, PayPalManager $paypalManager,
                                 PaymentTransactionManager $transactionManager)
    {
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
                $this->fulfillTransaction($transaction);
                $transactionManager->closeTransaction($transaction, 'fulfilled');
            } else
                $transactionManager->closeTransaction($transaction, 'vendor_refused');
        }
        return redirect()->route('accountcurrency.transaction', ['id' => $transaction->id]);
    }

    public function paypalCancel(Request $request, PayPalManager $paypalManager,
                                 PaymentTransactionManager $transactionManager)
    {
        $transaction = $transactionManager->getTransactionFromExternalId($request->get('token'));
        if (!$transaction->open()) return 403;
        $paypalManager->cancelPayPalOrder($transaction);
        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    public function paypalWebhook(Request $request, PayPalManager $paypalManager,
                                  PaymentTransactionManager $transactionManager)
    {
        $eventType = $request->get('event_type');
        Log::debug('Webhook occurred for event type: ' . $eventType);
        $verified = $paypalManager->verifyWebhookIsFromPayPal($request);
        if (!$verified) {
            Log::warning('Call from a PayPal Webhook could not be verified: ' . $request);
            return abort(401, 'Unverified');
        }
        //TODO: Paypal Webhook functionality
        return response('OK', 200);
    }

    #endregion PayPal responses

    #region Subscriptions

    /**
     * @param Request $request
     * @param CardPaymentManager $cardPaymentManager
     * @param PaymentSubscriptionManager $subscriptionManager
     */
    public function newCardSubscription(Request $request, CardPaymentManager $cardPaymentManager,
                                        PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $cardId = $request->input('cardId', null);
        $card = null;
        $card = $cardId ? $cardPaymentManager->getCardFor($user, $cardId)
            : $cardPaymentManager->getDefaultCardFor($user);
        if (!$card) return response("Default card couldn't be found or isn't valid.", 400);

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }

        if ($baseDetails['items']) return response("Subscription can't have items on it.", 400);

        return $subscriptionManager->createSubscription(
            $user, 'authorizenet', $card->id, null,
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['recurringInterval']
        )->toSubscriptionOfferArray();
    }

    /**
     * @param Request $request
     * @param PayPalManager $paypalManager
     * @param PaymentSubscriptionManager $subscriptionManager
     */
    public function newPayPalSubscription(Request $request, PayPalManager $paypalManager,
                                        PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $baseDetails = null;
        try {
            $baseDetails = $this->parseBaseRequest($request);
        } catch (Exception $e) {
            return response($e->getMessage(), 400);
        }

        if ($baseDetails['items']) return response("Subscription can't have items on it.", 400);

        $interval = $baseDetails['recurringInterval'];
        if (!$interval) return response("Subscription requires a recurring interval set.", 400);

        $planId = $paypalManager->getSubscriptionPlan($interval);
        if (!$planId) {
            Log::error("Couldn't find a PayPal PlanID for the recurring interval " . $interval);
            return response("Unable to create the subscription due to configuration error.", 500);
        }

        return $subscriptionManager->createSubscription(
            $user, 'paypal', 'paypal_unattributed', $planId,
            $baseDetails['accountCurrencyUsd'],
            $interval
        )->toSubscriptionOfferArray();
    }

    public function declineSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('token', null);

        if (!$subscriptionId || !$user) return abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->getAid() || !($subscription->status == 'approval_pending')) return abort(403);

        $subscriptionManager->closeSubscription($subscription, 'user_declined');
        return "Subscription Declined";
    }

    public function viewSubscription(PaymentSubscriptionManager $subscriptionManager, string $id)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$id) return abort(401);

        $subscription = $subscriptionManager->getSubscription($id);

        if ($subscription->accountId != $user->getAid()) return abort(403);

        return view('account-currency-subscription')->with([
            'subscription' => $subscription->toArray()
        ]);
    }

    public function acceptSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('token', null);

        if (!$subscriptionId || !$user) return abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->getAid() || !$subscription->open()) return abort(403);

        // If this is a paypal transaction, we move over to their process
        if ($subscription->vendor == 'paypal') {

            /** @var PayPalManager $payPalManager */
            $payPalManager = resolve('App\Payment\PayPalManager');
            try {
                $approvalUrl = $payPalManager->startPayPalSubscriptionFor($user, $subscription);
                return redirect($approvalUrl);
            } catch (Exception $e) {
                Log::info("Error during starting paypal subscription: " . $e);
                return abort(500);
            }
        }

        //Otherwise we mark it as active and leave for the scheduler
        $subscriptionManager->setSubscriptionAsActive($subscription);
        return redirect()->route('accountcurrency.subscription', [
            'id' => $subscription->id
        ]);
    }

    public function CancelSubscription(Request $request, PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $subscriptionId = $request->input('id', null);

        if (!$subscriptionId || !$user) return abort(403);

        $subscription = $subscriptionManager->getSubscription($subscriptionId);

        if ($subscription->accountId != $user->getAid()) return abort(403);

        $subscriptionManager->closeSubscription($subscription, 'cancelled');
        return "Subscription Cancelled.";

    }
    #endregion Subscriptions

}
