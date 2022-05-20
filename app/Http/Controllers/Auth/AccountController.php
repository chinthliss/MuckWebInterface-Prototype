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
use App\User as User;

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
            event(new Login($this->guard()::class, $user, $remember));
            //TODO: Look better at implementing loginThrottle
            // $this->clearLoginAttempts($request);

            //TODO: Remove test message in login
            $request->session()->flash('message-success', 'You have logged in! (And this is a test message.)');
            $response = array(
                'status' => 'success',
                'redirectUrl' => redirect()->intended(route('multiplayer.home'))->getTargetUrl(),
                'message' => 'Login successful. Please refresh page.'
            );
            return response()->json($response);
        } else {
            $user = $this->guard()->getProvider()->retrieveByCredentials($request->only('email'));
            event(new Failed($this->guard()::class, $user, $request->only('email', 'password')));
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

        /** @var User $user */
        $user = $this->guard()->getProvider()->createAccount($request['email'], $request['password']);

        event(new Registered($user));

        $remember = $request->has('forget') ? !$request['forget'] : true;
        $this->guard()->login($user, $remember);

        event(new Login($this->guard()::class, $user, $remember));

        // Set referral on new account if one is in the session
        if ($request->session()->has('account.referral')) {
            $user->setAccountProperty('tutor', $request->session()->get('account.referral'));
        }

        $response = array(
            'status' => 'success',
            'redirectUrl' => redirect()->intended(route('multiplayer.home'))->getTargetUrl(),
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
        event(new Logout($this->guard()::class, $user));
        return redirect()->route('login');
    }

    public function lockedAccount(Request $request)
    {
        //Ensure the account is actually locked!
        /** @var User $user */
        $user = $this->guard()->user();
        if (!$user || !$user->lockedAt) return redirect()->route('home');
        return view('auth.account-locked');
    }

    public function updateAvatarPreference(Request $request)
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $value = $request->get('value');
        if (!in_array($value, ['hidden', 'clean', 'default', 'explicit'])) abort(400);
        $user->setAvatarPreference($value);
    }

    /**
     * Show the main account page
     */
    public function show(PaymentSubscriptionManager $subscriptionManager)
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $subscriptionsUnparsed = $subscriptionManager->getSubscriptionsFor($user->getAid());
        $subscriptions = [];
        $subscriptionActive = false; // A subscription covers 'now'
        $subscriptionRenewing = false; // A subscription is renewing
        $subscriptionExpires = null; // latest date a subscription expires
        foreach ($subscriptionsUnparsed as $subscription) {
            if ($subscription->status === 'user_declined' || $subscription->status === 'approval_pending') continue;
            if ($subscription->renewing()) $subscriptionRenewing = true;
            if ($subscription->active()) {
                $subscriptionActive = true;
                if (!$subscriptionExpires || $subscription->expires() > $subscriptionExpires)
                    $subscriptionExpires = $subscription->expires();
            }
            array_push($subscriptions, $subscription->toArray());
        }

        return view('auth.account', [
            'user' => $user,
            'subscriptions' => $subscriptions,
            'subscriptionActive' => $subscriptionActive,
            'subscriptionRenewing' => $subscriptionRenewing,
            'subscriptionExpires' => $subscriptionExpires,
            'avatarPreference' => $user->getAvatarPreference()
        ]);
    }
}
