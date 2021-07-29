<?php

namespace App\Http\Controllers\Auth;

use App\User as User;
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
     */
    protected function redirectTo()
    {
        return route('multiplayer.home');
    }

    /**
     * For changing to an entirely new email
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     */
    public function changeEmail(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email' => 'required|email'
        ]);
        $user = auth()->user();
        if (!$this->guard()->getProvider()->validateCredentials($user, ['password'=>$request['password']])) {
            throw ValidationException::withMessages(['password'=>["Password provided doesn't match existing password"]]);
        }
        if (!$this->guard()->getprovider()->isEmailAvailable($request['email'])) {
            throw ValidationException::withMessages(['email'=>["This email is already associated with an account. You'll need to raise a ticket for this if you want to use this email."]]);
        }
        $user->setEmail($request['email']);
        $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed');
    }

    public function useExistingEmail(Request $request)
    {
        $request->validate(['email'=>'required']);
        /** @var User $user */
        $user = auth()->user();
        $emails = $user->getEmails();
        if (!array_key_exists($request['email'], $emails)) {
            throw ValidationException::withMessages(['email'=>["Email isn't associated with this account."]]);
        }
        $user->setEmail($request['email']);
        if (!$emails[$request['email']]['verified_at']) {
            $user->sendEmailVerificationNotification();
        }
        return redirect()->route('auth.account');
    }

    /**
     * For using an email already associated with the account
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws ValidationException
     */
    public function useEmail(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email' => 'required|email'
        ]);
        $user = auth()->user();
        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password'=>$request['password']])) {
            throw ValidationException::withMessages(['password'=>["Password provided doesn't match existing password"]]);
        }
        if (!array_key_exists($request['email'], $user->getEmails())) {
            throw ValidationException::withMessages(['email'=>["Email not found."]]);
        }
        $user->setEmail($request['email']);
        if (!$user->hasVerifiedEmail()) $user->sendEmailVerificationNotification();
        return view('auth.email-change-processed');
    }

    public function showChangeEmail()
    {
        return view('auth.email-change');
    }

}
