<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\AccountController;
use Illuminate\Http\Request;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;

// Deals with resetting and changing passwords.
class AccountPasswordController extends Controller
{
    public function guard()
    {
        return auth()->guard('account');
    }

    public function showReset()
    {
        return view('auth.password-reset');
    }

    public function showForgotten()
    {
        return view('auth.password-forgotten');
    }

    public function showChange()
    {
        return view('auth.password-change');
    }

    public function showEmailSent(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $user = $this->guard()->getProvider()->retrieveByCredentials($request->only('email'));
        if ($user) {
            $user->notify(new ResetPassword);
        }
        //Show view either way
        return view('auth.password-reset-sent');
    }

    public function resetPassword(Request $request, AccountController $accountController, $id)
    {
        $request->validate([
            'password' => 'required|confirmed|max:255'
        ]);
        if ($passwordCheck = $accountController->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password'=>$passwordCheck]);
        }
        $user = $this->guard()->getProvider()->retrieveById($id);
        $user->setPassword($request['password']);
        event(new PasswordReset($user));
        // TODO: Maybe log in user after successful password reset
        //TODO: Look for other things to do on password change - such as change remember_me
        return view('auth.password-reset-processed');
    }

    public function changePassword(Request $request, AccountController $accountController)
    {
        $request->validate([
            'password' => 'required|confirmed|max:255|different:oldpassword'
        ]);
        $user = auth()->user();
        if (!auth()->guard()->getProvider()->validateCredentials($user, ['password'=>$request['oldpassword']])) {
            throw ValidationException::withMessages(['oldpassword'=>["Password provided doesn't match existing password"]]);
        }
        if ($passwordCheck = $accountController->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password'=>$passwordCheck]);
        }

        $user->setPassword($request['password']);
        //TODO: Look for other things to do on password change - such as change remember_me or event
        return view('auth.password-change-processed');
    }

}
