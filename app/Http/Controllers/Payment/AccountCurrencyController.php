<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\CardPaymentManager;
use App\Http\Controllers\Controller;
use App\User;

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
}
