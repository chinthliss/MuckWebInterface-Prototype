<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\VerifiesEmails;

// Deals with emails
class AccountEmailController extends Controller
{
    use VerifiesEmails;

    public function guard()
    {
        return auth()->guard('account');
    }

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function changeEmail(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email' => 'required|email'
        ]);
        $user = auth()->user();
        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password'=>$request['password']])) {
            throw ValidationException::withMessages(['password'=>["Password provided doesn't match existing password"]]);
        }
        if (!auth()->guard()->getprovider()->isEmailAvailable($request['email'])) {
            throw ValidationException::withMessages(['email'=>["This email is already associated with an account. You'll need to raise a ticket for this if you want to use this email."]]);
        }
        $user->setEmail($request['email']);
        $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed');
    }


    public function showChangeEmail()
    {
        return view('auth.email-change');
    }

}
