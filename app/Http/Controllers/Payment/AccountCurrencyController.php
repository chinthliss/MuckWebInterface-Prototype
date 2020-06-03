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
        $user = auth()->guard()->user();
        $paymentProfile = $cardPaymentManager->loadProfileFor($user);

        $parsedSuggestedAmounts = [];
        foreach ($this->suggestedAmounts as $amount) {
            $parsedSuggestedAmounts[$amount] = $muck->usdToAccountCurrency($amount);
        }
        return view('account-currency')->with([
            'account' => $user->getAid(),
            'defaultCardMaskedNumber' => $paymentProfile->getDefaultCard()->maskedCardNumber(),
            'suggestedAmounts' => $parsedSuggestedAmounts
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
