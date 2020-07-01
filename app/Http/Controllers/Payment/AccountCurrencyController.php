<?php

namespace App\Http\Controllers\Payment;

use App\Payment\CardPaymentManager;
use App\Muck\MuckConnection;
use App\Http\Controllers\Controller;
use App\Payment\PaymentTransaction;
use App\Payment\PaymentTransactionManager;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountCurrencyController extends Controller
{
    private $suggestedAmounts = [5, 10, 20, 50];

    public function show(CardPaymentManager $cardPaymentManager, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        $defaultCard = $cardPaymentManager->getDefaultCardFor($user);

        $parsedSuggestedAmounts = [];
        foreach ($this->suggestedAmounts as $amount) {
            $parsedSuggestedAmounts[$amount] = $muck->usdToAccountCurrency($amount);
        }


        return view('account-currency')->with([
            'account' => $user->getAid(),
            'defaultCardMaskedNumber' => ($defaultCard ? $defaultCard->maskedCardNumber() : null),
            'suggestedAmounts' => $parsedSuggestedAmounts
        ]);
    }

    /**
     * @param Request{amount} $request
     * @param MuckConnection $muck
     * @return void|int;
     */
    public function usdToAccountCurrency(Request $request, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) return abort(401);

        $amount = (int)$request->input('amount', 0);
        if ($amount < 5) return abort(400);

        return $muck->usdToAccountCurrency($amount);
    }


    /**
     * @param Request $request
     * @param CardPaymentManager $cardPaymentManager
     * @param PaymentTransactionManager $transactionManager
     * @return array|void
     */
    public function newCardTransaction(Request $request, CardPaymentManager $cardPaymentManager,
                                       PaymentTransactionManager $transactionManager)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) return abort(401);

        $cardId = $request->input('cardId', null);
        $card = null;
        if ($cardId !== 'paypal') {

            $card = $cardId ? $cardPaymentManager->getCardFor($user, $cardId)
                : $cardPaymentManager->getDefaultCardFor($user);
            if (!$card) return abort(400);
        }

        $amountUsd = (int)$request->input('amountUsd', 0);
        if (!$amountUsd || $amountUsd < 5) return abort(400);

        $recurringInterval = $request->has('recurringInterval') ? (int)$request['recurringInterval'] : null;

        //Item code was previously disabled but leaving room for it here.
        $items = $request->has('items') ? $request['items'] : [];
        if ($items) return abort(501); //Not implemented

        return $transactionManager->createCardTransaction(
            $user, $card, $amountUsd, $items, $recurringInterval
        );
    }

    /**
     * @param PaymentTransaction $transaction
     * @return int actualAmountEarned
     */
    private function fulfillTransaction(PaymentTransaction $transaction)
    {
        //Actual mako adjustment is done by the MUCK still, due to ingame triggers
        $muck = resolve('App\Muck\MuckConnection');
        return $muck->adjustAccountCurrency(
            $transaction->accountId,
            $transaction->totalPriceUsd,
            $transaction->accountCurrencyRewarded,
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

        $transactionManager->closeTransaction($transaction->id, 'user_declined');
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

        $paid = false;
        if ($transaction->cardPaymentId) {
            $cardPaymentManager = resolve('App\Payment\CardPaymentManager');
            $card = $cardPaymentManager->getCardFor($user, $transaction->cardPaymentId);
            try {
                $cardPaymentManager->chargeCardFor($user, $card, $transaction->totalPriceUsd);
                $paid = true;
            } catch (\Exception $e) {
                Log::info("Error during card payment: " . $e);
            }
        }
        if ($transaction->payPalId) {
            throw new \Error("PayPal route hasn't been implemented.");
            $paid = true;
        }
        if ($paid) {
            $transactionManager->closeTransaction($transaction->id, 'fulfilled');
            $actualAmount = $this->fulfillTransaction($transaction);
            return "Transaction complete and credited to your account. " .
                "The total amount earned was " . $actualAmount . ".";
        } else
            return "The payment didn't process correctly or wasn't accepted.";

        return "Something went wrong and the transaction failed. Please notify staff of this.";
    }

}
