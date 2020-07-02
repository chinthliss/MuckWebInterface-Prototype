<?php

namespace App\Http\Controllers\Payment;

use App\Payment\Card;
use App\Payment\CardPaymentManager;
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
        $user = auth()->user();

        $cards = [];
        foreach ($cardPaymentManager->getCardsFor($user) as $card) {
            array_push($cards, $card->toArray());
        }

        return view('auth.card-management', [
            'profileId' => $cardPaymentManager->getCustomerIdFor($user),
            'cards' => $cards,
            'sealId' => config('services.authorizenet.sealId')
        ]);
    }

    public function addCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        $cardNumber = $request['cardNumber'];
        $expiryDate = $request['expiryDate'];
        $securityCode = $request['securityCode'];
        $errors = Card::findIssuesWithAddCardParameters($cardNumber, $expiryDate, $securityCode);
        if ($errors) throw ValidationException::withMessages($errors);

        /** @var User $user */
        $user = auth()->user();
        try {
            $card = $cardPaymentManager->createCardFor($user, $cardNumber, $expiryDate, $securityCode);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['cardNumber'=>'The given card was rejected by the authorization server.']);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return response(json_encode($card->toArray()), 200);
    }

    public function deleteCard(Request $request, CardPaymentManager $cardPaymentManager)
    {
        $cardId = $request['id'];
        if (!$cardId) return response('Card ID missing', 400);

        /** @var User $user */
        $user = auth()->user();
        try {
            $card = $cardPaymentManager->getCardFor($user, $cardId);
            $cardPaymentManager->deleteCardFor($user, $card);
        } catch (\Throwable $e) {
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
        $user = auth()->user();
        try {
            $card = $cardPaymentManager->getCardFor($user, $cardId);
            $cardPaymentManager->setDefaultCardFor($user, $card);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            throw ValidationException::withMessages(['cardNumber'=>'An internal server error occurred. The actual error has been logged for staff to review.']);
        }
        return response("OK", 200);
    }
}
