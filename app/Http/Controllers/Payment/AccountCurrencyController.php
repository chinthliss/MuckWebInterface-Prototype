<?php

namespace App\Http\Controllers\Payment;

use App\Payment\CardPaymentManager;
use App\Muck\MuckConnection;
use App\Http\Controllers\Controller;
use App\Payment\PaymentTransaction;
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
        $amountUsd = (int)$request->input('amount', 0);
        if (!$amountUsd || $amountUsd < self::minimumAmountUsd) return abort(400);

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

        return $transactionManager->createPayPalTransaction(
            $user,
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items'],
            $baseDetails['recurringInterval']
        )->toTransactionArray();

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

        return $transactionManager->createCardTransaction(
            $user, $card,
            $baseDetails['accountCurrencyUsd'],
            $baseDetails['items'],
            $baseDetails['recurringInterval']
        )->toTransactionArray();
    }

    /**
     * @param PaymentTransaction $transaction
     * @return int actualAmountEarned
     */
    private function fulfillTransaction(PaymentTransaction $transaction): int
    {
        //Actual mako adjustment is done by the MUCK still, due to ingame triggers
        $muck = resolve('App\Muck\MuckConnection');
        if ($transaction->items) {
            Log::error("Transaction " . $transaction->id . " tried to fulfill items and the item code isn't done!");
        }
        return $muck->adjustAccountCurrency(
            $transaction->accountId,
            $transaction->accountCurrencyPriceUsd,
            $transaction->accountCurrencyQuoted,
            $transaction->recurringInterval != null
        );
    }

    public function declineTransaction(Request $request, PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        $transactionId = $request->input('token', null);

        if (!$transactionId || !$user) return abort(403);

        $transaction = $transactionManager->getTransaction($transactionId);

        if ($transaction->accountId != $user->getAid() || !$transaction->open) return abort(403);

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

        if ($transaction->accountId != $user->getAid() || !$transaction->open) return abort(403);

        // If this is a paypal transaction, we create an order with them and redirect user to their approval
        if ($transaction->type == 'paypal') {
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
        $paid = false;

        if ($transaction->type == 'card') {
            $cardPaymentManager = resolve('App\Payment\CardPaymentManager');
            $card = $cardPaymentManager->getCardFor($user, $transaction->paymentProfileId);
            try {
                $externalId = $cardPaymentManager->chargeCardFor($user, $card, $transaction);
                $transactionManager->updateExternalId($transaction, $externalId);
                $paid = true;
            } catch (Exception $e) {
                Log::info("Error during card payment: " . $e);
            }
        }

        if ($paid) {
            $actualAmount = $this->fulfillTransaction($transaction);
            $transactionManager->closeTransaction($transaction, 'fulfilled', $actualAmount);
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
        if (!$transaction || !$transaction->externalId) {
            Log::error("PayPal - Told order " . $token . " has been accepted " .
                ", but either failed to look it up or looked up row is missing externalID.");
            abort(500);
        }
        if (!$transaction->open) return 403;
        $paid = $paypalManager->completePayPalOrder($transaction);
        if ($paid) {
            $actualAmount = $this->fulfillTransaction($transaction);
            $transactionManager->closeTransaction($transaction, 'fulfilled', $actualAmount);
        } else
            $transactionManager->closeTransaction($transaction, 'vendor_refused');
        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }

    public function paypalCancel(Request $request, PayPalManager $paypalManager,
                                 PaymentTransactionManager $transactionManager)
    {
        $transaction = $transactionManager->getTransactionFromExternalId($request->get('token'));
        if (!$transaction->open) return 403;
        $paypalManager->cancelPayPalOrder($transaction);
        return view('account-currency-transaction')->with([
            'transaction' => $transaction->toArray()
        ]);
    }
    #endregion PayPal responses


}
