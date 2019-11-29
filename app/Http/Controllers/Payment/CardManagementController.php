<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\CardPaymentManager;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class CardManagementController extends Controller
{
    public function show(CardPaymentManager $cardPaymentManager)
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        $profile = $cardPaymentManager->loadProfileFor($user->getAid());
        $result = $cardPaymentManager->test();

        return view('auth.card-management', [
            'response' => $result,
            'profile' => $profile
        ]);
    }

    public function addCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        $profile = $cardPaymentManager->loadProfileFor($user->getAid());
        $errors = $cardPaymentManager->findIssuesWithAddCardParameters(
            $request['cardNumber'], $request['expiryDate'], $request['securityCode']
        );
        if ($errors) throw ValidationException::withMessages($errors);

        abort(501);
    }
}
