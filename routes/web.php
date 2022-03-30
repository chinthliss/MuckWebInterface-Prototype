<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\AccountEmailController;
use App\Http\Controllers\AccountNotificationsController;
use App\Http\Controllers\Auth\AccountPasswordController;
use App\Http\Controllers\Auth\TermsOfServiceController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\MultiplayerController;
use App\Http\Controllers\SingleplayerController;
use App\Http\Controllers\Payment\AccountCurrencyController;
use App\Http\Controllers\Payment\CardManagementController;
use App\Http\Controllers\Payment\PatreonController;
use App\Http\Controllers\Payment\PayPalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SupportTicketController;

/*
|--------------------------------------------------------------------------
| Pages that are always available
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'show'])
    ->name('home');

//Character Profiles
Route::get('c/{name}', [MultiplayerController::class, 'showCharacter'])
    ->name('multiplayer.character.view');

//Character Avatar related images (Has exceptions in LoadActiveCharacter for optimization)
Route::get('a/{name}', [AvatarController::class, 'getAvatarFromCharacterName'])
    ->name('multiplayer.avatar.render');
Route::get('avatar/gradient/{name}', [AvatarController::class, 'getGradient'])
    ->name('avatar.gradient.image');
Route::get('avatar/gradient/preview/{code?}', [AvatarController::class, 'getGradientPreview'])
    ->name('avatar.gradient.previewimage');
Route::get('avatar/item/{id}', [AvatarController::class, 'getAvatarItem'])
    ->name('multiplayer.avatar.item');
Route::get('avatar/itempreview/{id}', [AvatarController::class, 'getAvatarItemPreview'])
    ->name('multiplayer.avatar.itempreview');

//Multiplayer getting started - always available since it gives instructions on creating an account.
Route::get('multiplayer/gettingstarted', [MultiplayerController::class, 'showGettingStarted'])
    ->name('multiplayer.gettingstarted');

//Terms of service - always viewable, does challenge if logged in.
Route::get('account/termsofservice', [TermsOfServiceController::class, 'view'])
    ->name('auth.account.termsofservice');
Route::post('account/termsofservice', [TermsOfServiceController::class, 'accept']);

//Paypal Notifications - this route is exempt from CSRF token. Controlled in the middleware.
Route::post('accountcurrency/paypal_webhook', [PayPalController::class, 'paypalWebhook']);

Route::get('/roadmap', [HomeController::class, 'showRoadmap'])
    ->name('roadmap');

//Information page for locked accounts
Route::get('/accountlocked', [AccountController::class, 'lockedAccount'])
    ->name('auth.account.locked');


/*
|--------------------------------------------------------------------------
| Pages that are only available when NOT logged in
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['guest']], function () {
    Route::get('login', [AccountController::class, 'showLoginForm'])
        ->name('login');
    Route::post('account/login', [AccountController::class, 'loginAccount'])
        ->name('auth.account.login')->middleware('throttle:8,1');
    Route::post('account/create', [AccountController::class, 'createAccount'])
        ->name('auth.account.create');

    //Password forgot / reset
    Route::get('account/passwordforgotten', [AccountPasswordController::class, 'showForgotten'])
        ->name('auth.account.passwordforgotten');
    Route::post('account/passwordforgotten', [AccountPasswordController::class, 'showEmailSent'])
        ->middleware('throttle:3,1');
    Route::get('account/passwordreset/{id}/{hash}', [AccountPasswordController::class, 'showReset'])
        ->name('auth.account.passwordreset')->middleware('signed', 'throttle:8,1');
    Route::post('account/passwordreset/{id}/{hash}', [AccountPasswordController::class, 'resetPassword'])
        ->middleware('signed', 'throttle:8,1');
});

/*
|--------------------------------------------------------------------------
| Pages that require a login but doesn't need verification or the TOS to be agreed to.
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account']], function () {
    Route::post('logout', [AccountController::class, 'logout'])->name('logout');
    Route::get('account/verifyemail', [AccountEmailController::class, 'show'])
        ->name('verification.notice'); // Name is required for Laravel's verification middleware
    Route::get('account/verifyemail/{id}/{hash}', [AccountEmailController::class, 'verify'])
        ->name('auth.account.verifyemail')->middleware('signed', 'throttle:8,1');
    Route::get('account/resendverifyemail', [AccountEmailController::class, 'resend'])
        ->name('auth.account.resendverifyemail')->middleware('throttle:8,1');
});

/*
|--------------------------------------------------------------------------
| Core pages that require a verified account and the TOS agreed to
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed']], function () {

    //Account
    Route::get('account', [AccountController::class, 'show'])->name('auth.account');
    Route::post('account/setactivecharacter', [MultiplayerController::class, 'setActiveCharacter'])
        ->name('multiplayer.character.set');

    //Password change
    Route::get('account/changepassword', [AccountPasswordController::class, 'showChange'])
        ->name('auth.account.passwordchange');
    Route::post('account/changepassword', [AccountPasswordController::class, 'changePassword']);

    //Email change
    Route::get('account/changeemail', [AccountEmailController::class, 'showChangeEmail'])
        ->name('auth.account.emailchange');
    Route::post('account/useexistingemail', [AccountEmailController::class, 'useExistingEmail']);
    Route::post('account/changeemail', [AccountEmailController::class, 'changeEmail']);

    //Preference change
    Route::post('account/updatePreference', [AccountController::class, 'updatePreference']);

    //Card Management
    Route::get('account/cardmanagement', [CardManagementController::class, 'show'])
        ->name('payment.cardmanagement');
    Route::post('account/cardmanagement', [CardManagementController::class, 'addCard'])
        ->name('payment.cardmanagement.add');
    Route::delete('account/cardmanagement', [CardManagementController::class, 'deleteCard'])
        ->name('payment.cardmanagement.delete');
    Route::patch('account/cardmanagement', [CardManagementController::class, 'updateDefaultCard']);

    //Notifications
    Route::get('account/notifications', [AccountNotificationsController::class, 'show'])
        ->name('account.notifications');
    Route::get('account/notifications/api', [AccountNotificationsController::class, 'getNotifications'])
        ->name('account.notifications.api');
    Route::delete('account/notifications/api/{id}', [AccountNotificationsController::class, 'deleteNotification']);
    Route::delete('account/notifications/api', [AccountNotificationsController::class, 'deleteAllNotifications']);

    //Support (User)
    Route::prefix('support')->group(function () {
        Route::get('/', [SupportTicketController::class, 'showUserHome'])
            ->name('support.user.home');
        Route::get('tickets', [SupportTicketController::class, 'getUserTickets'])
            ->name('support.user.tickets');
        Route::get('ticket/{id}', [SupportTicketController::class, 'showUserTicket'])
            ->name('support.user.ticket');
        //Also uses support.user.ticket as route
        Route::post('ticket/{id}', [SupportTicketController::class, 'handleUserUpdate']);
        Route::get('ticket/{id}/updatedAt', [SupportTicketController::class, 'getUpdatedAt'])
            ->name('support.getUpdatedAt'); // Shared with agent getUpdatedAt
        Route::get('newticket', [SupportTicketController::class, 'showUserRaiseTicket'])
            ->name('support.user.new');
        Route::post('newticket', [SupportTicketController::class, 'processUserRaiseTicket']);
    });

    //Account Currency
    Route::get('accountcurrency', [AccountCurrencyController::class, 'show'])
        ->name('accountcurrency');
    Route::post('accountcurrency/fromUsd', [AccountCurrencyController::class, 'usdToAccountCurrency']);
    Route::post('accountcurrency/newCardTransaction', [AccountCurrencyController::class, 'newCardTransaction']);
    Route::post('accountcurrency/newPayPalTransaction', [AccountCurrencyController::class, 'newPayPalTransaction']);
    Route::post('accountcurrency/declineTransaction', [AccountCurrencyController::class, 'declineTransaction']);
    Route::get('accountcurrency/acceptTransaction', [AccountCurrencyController::class, 'acceptTransaction']);
    Route::get('accountcurrency/transaction/{id}', [AccountCurrencyController::class, 'viewTransaction'])
        ->name('accountcurrency.transaction');
    Route::get('accountcurrency/history/{accountId?}', [AccountCurrencyController::class, 'viewTransactions'])
        ->name('accountcurrency.transactions');
    Route::get('accountcurrency/paypal_order_return', [PayPalController::class, 'paypalOrderReturn'])
        ->name('accountcurrency.paypal.order.return');
    Route::get('accountcurrency/paypal_order_cancel', [PayPalController::class, 'paypalOrderCancel'])
        ->name('accountcurrency.paypal.order.cancel');

    Route::post('accountcurrency/newCardSubscription', [AccountCurrencyController::class, 'newCardSubscription']);
    Route::post('accountcurrency/newPayPalSubscription', [AccountCurrencyController::class, 'newPayPalSubscription']);
    Route::post('accountcurrency/declineSubscription', [AccountCurrencyController::class, 'declineSubscription']);
    Route::post('accountcurrency/cancelSubscription', [AccountCurrencyController::class, 'cancelSubscription']);
    Route::get('accountcurrency/acceptSubscription', [AccountCurrencyController::class, 'acceptSubscription']);
    Route::get('accountcurrency/subscription/{id}', [AccountCurrencyController::class, 'viewSubscription'])
        ->name('accountcurrency.subscription');
    Route::get('accountcurrency/paypal_subscription_return', [PayPalController::class, 'paypalSubscriptionReturn'])
        ->name('accountcurrency.paypal.subscription.return');
    Route::get('accountcurrency/paypal_subscription_cancel', [PayPalController::class, 'paypalSubscriptionCancel'])
        ->name('accountcurrency.paypal.subscription.cancel');
});

/*
|--------------------------------------------------------------------------
| Multiplayer content that doesn't require a character
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed', 'not.locked']], function () {

    Route::get('multiplayer', [MultiplayerController::class, 'showMultiplayerDashboard'])
        ->name('multiplayer.home');

    Route::get('multiplayer/selectCharacter', [MultiplayerController::class, 'showCharacterSelect'])
        ->name('multiplayer.character.select');
    Route::post('multiplayer/buyCharacterslot', [MultiplayerController::class, 'buyCharacterSlot'])
        ->name('multiplayer.character.buySlot');

    Route::get('multiplayer/createCharacter', [MultiplayerController::class, 'showCharacterCreation'])
        ->name('multiplayer.character.create');
    Route::post('multiplayer/createCharacter', [MultiplayerController::class, 'createCharacter']);
    Route::get('multiplayer/characterGeneration', [MultiplayerController::class, 'showCharacterGeneration'])
        ->name('multiplayer.character.generate');
    Route::post('multiplayer/characterGeneration', [MultiplayerController::class, 'finalizeCharacter'])
        ->name('multiplayer.character.finalize');

    Route::get('multiplayer/changeCharacterPassword', [MultiplayerController::class, 'showChangeCharacterPassword'])
        ->name('multiplayer.character.changepassword');
    Route::post('multiplayer/changeCharacterPassword', [MultiplayerController::class, 'changeCharacterPassword']);
});

/*
|--------------------------------------------------------------------------
| Multiplayer content that requires a character
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed', 'not.locked', 'character']], function () {
    Route::get('multiplayer/avatargradients', [AvatarController::class, 'showUserAvatarGradients'])
        ->name('multiplayer.avatar.gradients');
    Route::get('multiplayer/avatar', [AvatarController::class, 'showAvatarEditor'])
        ->name('multiplayer.avatar');
    Route::get('multiplayer/connect', [MultiplayerController::class, 'showConnect'])
        ->name('multiplayer.connect');
    Route::get('avatar/edit/{code?}', [AvatarController::class, 'getAvatarFromUserCode'])
        ->name('multiplayer.avatar.edit.render');

});

/*
|--------------------------------------------------------------------------
| Singleplayer content
|--------------------------------------------------------------------------
*/
Route::prefix('singleplayer')->group(function () {
    //No additional middleware required
    Route::group([], function () {
        Route::get('/', [SingleplayerController::class, 'showHome'])
            ->name('singleplayer.home');
    });
});

/*
|--------------------------------------------------------------------------
| Staff pages
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed', 'role:staff']], function () {
    Route::get('admin', [AdminController::class, 'show'])
        ->name('admin.home');

    //Support (Agent)
    Route::prefix('support/agent/')->group(function () {
        Route::get('/', [SupportTicketController::class, 'showAgentHome'])
            ->name('support.agent.home');
        Route::get('tickets', [SupportTicketController::class, 'getAgentTickets'])
            ->name('support.agent.tickets');
        Route::get('ticket/{id}', [SupportTicketController::class, 'showAgentTicket'])
            ->name('support.agent.ticket');
        //Also uses support.agent.ticket as route
        Route::post('ticket/{id}', [SupportTicketController::class, 'handleAgentUpdate']);
        Route::get('newticket', [SupportTicketController::class, 'showAgentRaiseTicket'])
            ->name('support.agent.new');
        Route::post('newticket', [SupportTicketController::class, 'processAgentRaiseTicket']);
    });

    //Avatar Doll testing
    Route::prefix('admin/avatar/')->group(function () {
        Route::get('dolllist', [AvatarController::class, 'showAdminDollList'])
            ->name('admin.avatar.dolllist');
        Route::get('dolltest/{code?}', [AvatarController::class, 'showAdminDollTest'])
            ->name('admin.avatar.dolltest');
        Route::get('dolltest/{dollName}/thumbnail', [AvatarController::class, 'getThumbnailForDoll'])
            ->name('admin.avatar.dollthumbnail');
        Route::get('render/{code?}', [AvatarController::class, 'getAvatarFromAdminCode'])
            ->name('admin.avatar.render');
        Route::get('testall', [AvatarController::class, 'getAllAvatarsAsAGif']);
    });

    //Avatar Gradients
    Route::get('admin/avatargradients', [AvatarController::class, 'showAdminAvatarGradients'])
        ->name('admin.avatar.gradients');

    //Avatar Items
    Route::get('admin/avataritems', [AvatarController::class, 'showAdminAvatarItems'])
        ->name('admin.avatar.items');


});

/*
|--------------------------------------------------------------------------
| Admin pages
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed', 'role:admin']], function () {

    Route::get('admin/roles', [AdminController::class, 'showAccountRoles'])
        ->name('admin.roles');

    Route::get('admin/accounts', [AdminController::class, 'showAccountFinder'])
        ->name('admin.accounts');
    Route::get('admin/accounts/api', [AdminController::class, 'findAccounts'])
        ->name('admin.accounts.api');
    Route::get('admin/account/{accountId}', [AdminController::class, 'showAccount'])
        ->name('admin.account');

});

/*
|--------------------------------------------------------------------------
| Site Admin pages
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:account', 'verified', 'tos.agreed', 'role:siteadmin']], function () {
    Route::get('accountcurrency/subscriptions', [AccountCurrencyController::class, 'adminViewSubscriptions'])
        ->name('admin.subscriptions');
    Route::get('accountcurrency/subscriptions/api', [AccountCurrencyController::class, 'adminGetSubscriptions'])
        ->name('admin.subscriptions.api');

    Route::get('accountcurrency/transactions', [AccountCurrencyController::class, 'adminViewTransactions'])
        ->name('admin.transactions');
    Route::get('accountcurrency/transactions/api', [AccountCurrencyController::class, 'adminGetTransactions'])
        ->name('admin.transactions.api');

    Route::get('admin/patreons', [PatreonController::class, 'adminShow'])
        ->name('admin.patrons');
    Route::get('admin/patreons/api', [PatreonController::class, 'adminGetPatrons'])
        ->name('admin.patrons.api');

    Route::get('admin/logs', [AdminController::class, 'showLogViewer'])
        ->name('admin.logs');
    Route::get('admin/logs/{date}', [AdminController::class, 'getLogForDate']);
});
