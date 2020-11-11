<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

//Only available when NOT logged in
Route::group(['middleware' => ['web', 'guest']], function() {
    Route::get('/', 'WelcomeController@show')
        ->name('welcome');
    Route::get('login', 'Auth\AccountController@showLoginForm')
        ->name('login');
    Route::post('account/login', 'Auth\AccountController@loginAccount')
        ->name('auth.account.login')->middleware('throttle:8,1');
    Route::post('account/create', 'Auth\AccountController@createAccount')
        ->name('auth.account.create');
    //Password forgot / reset
    Route::get('account/passwordforgotten', 'Auth\AccountPasswordController@showForgotten')
        ->name('auth.account.passwordforgotten');
    Route::post('account/passwordforgotten', 'Auth\AccountPasswordController@showEmailSent')
        ->middleware('throttle:3,1');
    Route::get('account/passwordreset/{id}/{hash}', 'Auth\AccountPasswordController@showReset')
        ->name('auth.account.passwordreset')->middleware('signed', 'throttle:8,1');
    Route::post('account/passwordreset/{id}/{hash}', 'Auth\AccountPasswordController@resetPassword')
        ->middleware('signed', 'throttle:8,1');
});

//Requires an account but DOESN'T require verification or Terms of Service acceptance
Route::group(['middleware' => ['web', 'auth:account']], function() {
    Route::post('logout', 'Auth\AccountController@logout')->name('logout');
    Route::get('account/verifyemail', 'Auth\AccountEmailController@show')
        ->name('verification.notice'); // Name is required for Laravel's verification middleware
    Route::get('account/verifyemail/{id}/{hash}', 'Auth\AccountEmailController@verify')
        ->name('auth.account.verifyemail')->middleware('signed', 'throttle:8,1');
    Route::get('account/resendverifyemail', 'Auth\AccountEmailController@resend')
        ->name('auth.account.resendverifyemail')->middleware('throttle:8,1');
});

//Requires account, verification and terms of service acceptance
Route::group(['middleware' => ['web', 'auth:account', 'verified', 'tos.agreed']], function() {
    Route::get('home', 'HomeController@show')->name('home');


    Route::get('account', 'Auth\AccountController@show')->name('auth.account');
    //Password change
    Route::get('account/changepassword', 'Auth\AccountPasswordController@showChange')
        ->name('auth.account.passwordchange');
    Route::post('account/changepassword', 'Auth\AccountPasswordController@changePassword');
    //Email change
    Route::get('account/changeemail', 'Auth\AccountEmailController@showChangeEmail')
        ->name('auth.account.emailchange');
    Route::post('account/useexistingemail', 'Auth\AccountEmailController@useExistingEmail');
    Route::post('account/changeemail', 'Auth\AccountEmailController@changeEmail');
    //Preference change
    Route::post('account/updatePreference', 'Auth\AccountController@updatePreference');
    //Card Management
    Route::get('account/cardmanagement', 'Payment\CardManagementController@show')
        ->name('payment.cardmanagement');
    Route::post('account/cardmanagement', 'Payment\CardManagementController@addCard')
        ->name('payment.cardmanagement.add');
    Route::delete('account/cardmanagement', 'Payment\CardManagementController@deleteCard')
        ->name('payment.cardmanagement.delete');
    Route::patch('account/cardmanagement', 'Payment\CardManagementController@updateDefaultCard');

    //Account Currency
    Route::get('accountcurrency', 'Payment\AccountCurrencyController@show')
        ->name('accountcurrency');
    Route::post('accountcurrency/fromUsd', 'Payment\AccountCurrencyController@usdToAccountCurrency');
    Route::post('accountcurrency/newCardTransaction', 'Payment\AccountCurrencyController@newCardTransaction');
    Route::post('accountcurrency/newPayPalTransaction', 'Payment\AccountCurrencyController@newPayPalTransaction');
    Route::post('accountcurrency/declineTransaction', 'Payment\AccountCurrencyController@declineTransaction');
    Route::get('accountcurrency/acceptTransaction', 'Payment\AccountCurrencyController@acceptTransaction');
    Route::get('accountcurrency/transaction/{id}', 'Payment\AccountCurrencyController@viewTransaction')
        ->name('accountcurrency.transaction');
    Route::get('accountcurrency/history', 'Payment\AccountCurrencyController@viewTransactions');
    Route::post('accountcurrency/newCardSubscription', 'Payment\AccountCurrencyController@newCardSubscription');
    Route::post('accountcurrency/newPayPalSubscription', 'Payment\AccountCurrencyController@newPayPalSubscription');
    Route::post('accountcurrency/declineSubscription', 'Payment\AccountCurrencyController@declineSubscription');
    Route::post('accountcurrency/cancelSubscription', 'Payment\AccountCurrencyController@cancelSubscription');
    Route::get('accountcurrency/acceptSubscription', 'Payment\AccountCurrencyController@acceptSubscription');
    Route::get('accountcurrency/subscription/{id}', 'Payment\AccountCurrencyController@viewSubscription')
        ->name('accountcurrency.subscription');
    Route::get('accountcurrency/paypal_return', 'Payment\AccountCurrencyController@paypalReturn')
        ->name('accountcurrency.paypal.return');
    Route::get('accountcurrency/paypal_cancel', 'Payment\AccountCurrencyController@paypalCancel')
        ->name('accountcurrency.paypal.cancel');
});

//Website admin routes
Route::group(['middleware' => ['web', 'auth:account', 'verified', 'tos.agreed', 'role:admin']], function() {
    Route::get('admin', 'AdminController@show')
        ->name('admin.home');
    Route::get('admin/logs', 'AdminController@showLogViewer')
        ->name('admin.logs');
    Route::get('admin/logs/{date}', 'AdminController@getLogForDate');
});

//----------------------------------------
//Always available

//Character Profiles
Route::get('p/{characterName}', 'CharacterController@show')->name('character');

//Terms of service - always viewable, does challenge if logged in.
Route::get('account/termsofservice', 'Auth\TermsOfServiceController@view')
    ->name('auth.account.termsofservice');
Route::post('account/termsofservice', 'Auth\TermsOfServiceController@accept')
    ->name('auth.account.termsofservice');

//Paypal Notifications - this route is exempt from CSRF token. Controlled in the middleware.
Route::post('accountcurrency/paypal_webhook', 'Payment\AccountCurrencyController@paypalWebhook');
