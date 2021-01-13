<?php

namespace App\Http\Controllers\Payment;

use App\Payment\CardPaymentManager;
use App\Muck\MuckConnection;
use App\Http\Controllers\Controller;
use App\Payment\PaymentSubscription;
use App\Payment\PaymentSubscriptionManager;
use App\Payment\PaymentTransactionItemCatalogue;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPalManager;
use App\User;
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
            'defaultCardExpiryDate' => ($defaultCard ? $defaultCard->expiryDate->addMonth() : null),
            'suggestedAmounts' => $parsedSuggestedAmounts,
            'itemCatalogue' => $parsedItems,
            'stretchGoals' => $muck->stretchGoals()
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
                try {
                    $transactionManager->chargeTransaction($transaction);
                } catch (Exception $e) {
                    Log::info("Error during account currency card payment: " . $e);
                }
            }
        }

        if ($transaction->paid()) {
            $transactionManager->fulfillTransaction($transaction);
            $transactionManager->closeTransaction($transaction, 'fulfilled');
        } else
            $transactionManager->closeTransaction($transaction, 'vendor_refused');
        return redirect()->route('accountcurrency.transaction', [
            'id' => $transactionId
        ]);
    }

    public function viewTransaction(PaymentTransactionManager $transactionManager, string $id)
    {
        /** @var User $user */
        $user = auth()->user();
        $transaction = $transactionManager->getTransaction($id);

        if (!$transaction) return abort(404);
        if ($transaction->accountId != $user->getAid() && !$user->hasRole('admin')) return abort(403);

        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    public function viewTransactions(PaymentTransactionManager $transactionManager, int $accountId = null)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$accountId) $accountId = $user->getAid();
        else if ($accountId !== $user->getAid() && !$user->hasRole('admin')) return abort(403);

        return view('account-currency-transactions')->with([
            'transactions' => $transactionManager->getTransactionsFor($accountId)
        ]);
    }

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
        $subscription = $subscriptionManager->getSubscription($id);

        if (!$subscription) return abort(404);
        if ($subscription->accountId != $user->getAid() && !$user->hasRole('admin')) return abort(403);

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

        //Otherwise we mark it as active and attempt to process
        $subscriptionManager->setSubscriptionAsActive($subscription);
        $subscriptionManager->processSubscription($subscription);
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

    public function adminViewSubscriptions(PaymentSubscriptionManager $subscriptionManager)
    {

        return view('account-currency-subscriptions');
    }

    public function adminGetSubscriptions(PaymentSubscriptionManager $subscriptionManager)
    {
        $subscriptions = $subscriptionManager->getSubscriptions();
        return $subscriptions->map(function (PaymentSubscription $subscription) {
            return $subscription->toArray();
        });
    }

    #endregion Subscriptions

}
