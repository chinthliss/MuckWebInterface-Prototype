<?php

namespace App\Http\Controllers\Auth;

use App\TermsOfService;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class TermsOfServiceController extends Controller
{
    public function getHash()
    {
        return TermsOfService::getTermsOfServiceHash();
    }

    public function getContent()
    {
        return TermsOfService::getTermsOfService();
    }

    public function view(TermsOfService $termsOfService)
    {
        $user = auth()->user();
        return view('auth.terms-of-service')->with([
            'termsOfService' => $termsOfService->getTermsOfService(),
            'agreed' => $user && $user->getAgreedToTermsOfService(),
            'hash' => $termsOfService->getTermsOfServiceHash()
        ]);
    }

    public function accept(Request $request, TermsOfService $termsOfService)
    {
        $user = auth()->user();
        $user->storeTermsOfServiceAgreement($request['_hash']);
        return redirect()->intended(route('home'));
    }


}
