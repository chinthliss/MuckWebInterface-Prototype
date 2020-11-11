<?php

namespace App\Http\Controllers\Auth;

use App\Payment\PaymentSubscriptionManager;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Class AccountController
 * Handles requests relating to account login, creation, verification, password resets, etc.
 */
class AccountController extends Controller
{

    /**
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public function guard()
    {
        return auth()->guard('account');
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function findIssuesWithPassword(string $password)
    {
        $issues = array();
        if (strlen($password) < 3) array_push($issues, 'Password is too short (minimum width is 3 characters)');
        if (preg_match("/[\s]/", $password)) array_push($issues, 'Password can not contain spaces.');
        if (preg_match("/[^\x20-\x7E]/", $password)) array_push($issues, 'Password can only contain characters representable by ANSI.');
        return $issues;
    }

    public function loginAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|max:255',
            'password' => 'required|max:255'
        ]);
        //Check
        $remember = $request->has('forget') ? !$request['forget'] : true;
        $attemptResult = $this->guard()->attempt($request->only('email', 'password'), $remember);
        if ($attemptResult) {
            $request->session()->regenerate();
            $user = $this->guard()->user();
            event(new Login($this->guard(), $user, $remember));
            //TODO: Look better at implementing loginThrottle
            // $this->clearLoginAttempts($request);

            //TODO: Remove test message in login
            $request->session()->flash('message-success', 'You have logged in! (And this is a test message.)');
            $response = array(
                'status' => 'success',
                'redirectUrl' => redirect()->intended('/home')->getTargetUrl(),
                'message' => 'Login successful. Please refresh page.'
            );
            return response()->json($response);
        } else {
            $user = $this->guard()->getProvider()->retrieveByCredentials($request->only('email'));
            event(new Failed($this->guard(), $user, $request->only('email', 'password')));
            throw ValidationException::withMessages(['password' => ['Unrecognized Email/Password or Character/Password combination.']]);
        }
    }

    public function createAccount(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|max:255'
        ]);

        if (!$this->guard()->getProvider()->isEmailAvailable($request['email'])) {
            throw ValidationException::withMessages(['email' => ['This email is already in use.']]);
        }

        if ($passwordCheck = $this->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password' => $passwordCheck]);
        }

        $user = $this->guard()->getProvider()->createAccount($request['email'], $request['password']);

        event(new Registered($user));

        $remember = $request->has('forget') ? !$request['forget'] : true;
        $this->guard()->login($user, $remember);

        event(new Login($this->guard(), $user, $remember));

        $response = array(
            'status' => 'success',
            'redirectUrl' => redirect()->intended('/home')->getTargetUrl(),
            'message' => 'Account created successfully. Please refresh page.'
        );
        return response()->json($response);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = $this->guard()->user();
        $this->guard()->logout();
        $request->session()->invalidate();
        event(new Logout($this->guard(), $user));
        return redirect()->route('login');
    }

    /**
     * Update and save a preference
     */
    public function updatePreference(Request $request)
    {
        $user = $this->guard()->user();
        if ($user) {
            foreach ($request->all() as $preferenceName => $preferenceValue) {
                switch ($preferenceName) {
                    case 'hideAvatars':
                        $user->setPrefersNoAvatars($preferenceValue);
                        break;
                    case 'useFullWidth':
                        $user->setPrefersFullWidth($preferenceValue);
                        break;
                    default:
                        Log::warning('Unrecognized preference to update ' . $preferenceName
                            . ' for user ' . $user->getAid());
                }
            }
        }
    }

    /**
     * Show the main account page
     */
    public function show(PaymentSubscriptionManager $subscriptionManager)
    {
        $user = $this->guard()->user();
        $subscriptionsUnparsed = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $subscriptions = [];
        foreach ($subscriptionsUnparsed as $subscription) {
            if ($subscription->status === 'user_declined' || $subscription->status === 'approval_pending') continue;
            array_push($subscriptions, [
                'id' => $subscription->id,
                'type' => $subscription->type(),
                'amount_usd' => $subscription->amountUsd,
                'recurring_interval' => $subscription->recurringInterval,
                'created' => $subscription->createdAt,
                'closed' => $subscription->closedAt,
                'next_charge' => $subscription->nextChargeAt,
                'status' => $subscription->status,
                'url' => route('accountcurrency.subscription', ["id" => $subscription->id])
            ]);
        }
        return view('auth.account', [
            'user' => $user,
            'subscriptions' => $subscriptions
        ]);
    }
}
