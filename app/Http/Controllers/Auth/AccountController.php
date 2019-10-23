<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

/**
 * Class AccountController
 * Handles requests relating to account login, creation, verification, password resets, etc.
 */
class AccountController extends Controller
{

    /**
     * Show the login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function guard()
    {
        return auth()->guard('account');
    }

    public function findIssuesWithPassword(string $password)
    {
        $issues = array();
        if (strlen($password) < 3) array_push($issues, 'Password is too short (minimum width is 3 characters)');
        if (preg_match("/[\s]/", $password)) array_push($issues, 'Password can not contain spaces.');
        if (preg_match("/[^\x20-\x7E]/", $password)) array_push($issues, 'Password can only contain characters representable by ANSI.');
        return $issues;
    }

    //Triggers a change to another character and refreshes page
    public function switchCharacter(Request $request)
    {
        $user = $this->guard()->user();
        $session = $request->session();
        dd($session);
        $session->regenerate();
        return back();
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
            $user =  $this->guard()->user();
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
            throw ValidationException::withMessages(['password'=>['Unrecognized Email/Password or Character/Password combination.']]);
        }
    }

    public function createAccount(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|max:255'
        ]);

        if (!$this->guard()->getProvider()->isEmailAvailable($request['email'])) {
            throw ValidationException::withMessages(['email'=>['This email is already in use.']]);
        }

        if ($passwordCheck = $this->findIssuesWithPassword($request['password'])) {
            throw ValidationException::withMessages(['password'=>$passwordCheck]);
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $user = $this->guard()->user();
        $this->guard()->logout();
        $request->session()->invalidate();
        event(new Logout($this->guard(), $user));
        return redirect()->route('login');
    }

    public function show()
    {
        return view('auth.account', [
            'user' => $this->guard()->user()
        ]);
    }
}
