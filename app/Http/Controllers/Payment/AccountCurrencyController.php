<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\CardPaymentManager;
use App\Contracts\MuckConnection;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class AccountCurrencyController extends Controller
{
    public function show(CardPaymentManager $cardPaymentManager)
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        $paymentProfile = $cardPaymentManager->loadProfileFor($user);

        return view('account-currency')->with([
            'account' => $user->getAid(),
            'defaultCardMaskedNumber' => $paymentProfile->getDefaultCard()->maskedCardNumber()
        ]);
    }

    public function usdToAccountCurrency(Request $request, MuckConnection $muck)
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        if (!$user) return abort('403');

        $amount = $request['amount'];
        if (!$amount) return abort('400');

        return $muck->usdToAccountCurrency($amount);
    }
}
