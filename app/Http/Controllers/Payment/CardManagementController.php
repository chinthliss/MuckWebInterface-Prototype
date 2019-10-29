<?php

namespace App\Http\Controllers\Payment;

use App\CardPaymentManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class CardManagementController extends Controller
{
    public function show(CardPaymentManager $cardPaymentManager)
    {
        $result = $cardPaymentManager->test();

        return view('auth.card-management', [
            'response' => $result
        ]);
    }
}
