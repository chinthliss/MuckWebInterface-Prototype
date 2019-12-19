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
    //Card Management
    Route::get('account/cardmanagement', 'Payment\CardManagementController@show');
    Route::post('account/cardmanagement', 'Payment\CardManagementController@addCard');
    Route::delete('account/cardmanagement', 'Payment\CardManagementController@deleteCard');
    Route::patch('account/cardmanagement', 'Payment\CardManagementController@updateDefaultCard');

});

//Always available
//Character Profiles
Route::get('p/{characterName}', 'CharacterController@show')->name('character');
//Terms of service - always viewable, does challenge if logged in.
Route::get('account/termsofservice', 'Auth\TermsOfServiceController@view')
    ->name('auth.account.termsofservice');
