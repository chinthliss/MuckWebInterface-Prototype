<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\CardPaymentManager;
use App\Muck\MuckConnection;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class AccountCurrencyController extends Controller
{
    private $suggestedAmounts = [5, 10, 20, 50];

    public function show(CardPaymentManager $cardPaymentManager, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        $paymentProfile = $cardPaymentManager->loadProfileFor($user);
        $defaultCard = ($paymentProfile ? $paymentProfile->getDefaultCard() : null);

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
     * cardId can be 'paypal'
     * @param Request{cardId, amountUsd, [recurringInterval], [items]} $request
     * @param CardPaymentManager $cardPaymentManager
     * @param MuckConnection $muck
     * @return void|array{id}
     */
    public function newTransaction(Request $request, CardPaymentManager $cardPaymentManager, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) return abort(401);

        $purchase=[];
        $priceUsd = 0;
        $paymentProfile = $cardPaymentManager->loadOrCreateProfileFor($user);
        $cardId = $request->input('cardId', null);
        $card = null;
        if ($cardId !== 'paypal') {
            $card = $cardId ? $paymentProfile->getCard($cardId) : $paymentProfile->getDefaultCard();
            if (!$card) return abort(400);
        }

        $amountUsd = (int)$request->input('amountUsd', 0);
        if (!$amountUsd || $amountUsd < 5) return abort(400);
        $amountAccountCurrency = $muck->usdToAccountCurrency($amountUsd);
        if ($amountAccountCurrency) {
            array_push($purchase, $amountAccountCurrency . ' Mako');
            $priceUsd += $amountUsd;
        }

        $recurringInterval = $request->has('recurringInterval') ? (int)$request['recurringInterval'] : null;

        //Item code was previously disabled but leaving room for it here.
        $items = $request->has('items') ? $request['items'] : [];
        if ($items) return abort (501); //Not implemented

        // $card = $paymentProfile->getCard($request['cardId']);
        return [
            "token" => 1,
            "purchase" => implode('<br/>', $purchase),
            "price" => "$" . $priceUsd
        ];
    }

}
