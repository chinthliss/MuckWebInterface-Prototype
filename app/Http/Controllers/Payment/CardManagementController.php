<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\Card;
use App\CardPayment\CardPaymentManager;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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
        $cardNumber = $request['cardNumber'];
        $expiryDate = $request['expiryDate'];
        $securityCode = $request['securityCode'];
        $errors = $cardPaymentManager->findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode);
        if ($errors) throw ValidationException::withMessages($errors);

        /** @var User $user */
        $user = auth()->guard()->user();
        try {
            $profile = $cardPaymentManager->loadOrCreateProfileFor($user);
            $cardPaymentManager->createCardFor($profile, $cardNumber, $expiryDate, $securityCode);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return redirect()->refresh();
    }
}
