<?php

namespace App\Http\Controllers\Payment;

use App\CardPayment\Card;
use App\CardPayment\CardPaymentManager;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use net\authorize\api\contract\v1\DeleteCustomerPaymentProfileRequest;

class CardManagementController extends Controller
{
    public function show(CardPaymentManager $cardPaymentManager)
    {
        /** @var User $user */
        $user = auth()->guard()->user();
        $profile = $cardPaymentManager->loadProfileFor($user);

        $cards = [];
        if ($profile) {
            foreach ($profile->getCardIds() as $id) {
                array_push($cards, $profile->getCard($id));
            }
        }

        return view('auth.card-management', [
            'profileId' => ($profile ? $profile->getCustomerProfileId() : null),
            'cards' => $cards,
            'sealId' => config('services.authorize.sealId')
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
            $card = $cardPaymentManager->createCardFor($profile, $cardNumber, $expiryDate, $securityCode);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return response(json_encode($card), 200);
    }

    public function deleteCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        $cardId = $request['id'];
        if (!$cardId) return response('Card ID missing', 400);

        /** @var User $user */
        $user = auth()->guard()->user();
        try {
            $profile = $cardPaymentManager->loadOrCreateProfileFor($user);
            $card = $profile->getCard($cardId);
            $cardPaymentManager->deleteCardFor($profile, $card);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return response("OK", 200);
    }

    public function updateDefaultCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        $cardId = $request['id'];
        if (!$cardId) return response('Card ID missing', 400);
        /** @var User $user */
        $user = auth()->guard()->user();
        try {
            $profile = $cardPaymentManager->loadOrCreateProfileFor($user);
            $card = $profile->getCard($cardId);
            $cardPaymentManager->setDefaultCardFor($profile, $card);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return response("OK", 200);
    }
}
