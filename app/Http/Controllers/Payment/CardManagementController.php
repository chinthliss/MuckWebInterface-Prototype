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
        $profile = $cardPaymentManager->loadProfileFor($user);

        return view('auth.card-management', [
            'profile' => ($profile ? $profile->getCustomerProfileId() : null)
        ]);
    }

    public function addCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        $errors = $cardPaymentManager->findIssuesWithAddCardParameters(
            $request['cardNumber'], $request['expiryDate'], $request['securityCode']
        );
        if ($errors) throw ValidationException::withMessages($errors);

        /** @var User $user */
        $user = auth()->guard()->user();
        $profile = $cardPaymentManager->loadOrCreateProfileFor($user);
        $paymentProfile = $cardPaymentManager->createPaymentProfileFor($profile);
        return redirect()->refresh();
    }
}
