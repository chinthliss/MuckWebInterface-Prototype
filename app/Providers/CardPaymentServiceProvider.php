<?php

namespace App\Providers;

use App\CardPayment\AuthorizeNetCardPaymentCustomerProfile;
use App\CardPayment\CardPaymentManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;


class CardPaymentServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //This should be loaded from config if ever properly abstracted.
        $this->app->singleton('CardPaymentCustomerProfile', function($app) {
            return AuthorizeNetCardPaymentCustomerProfile::class;
        });

        $this->app->singleton(CardPaymentManager::class, function($app) {
            $loginId = config('services.authorize.loginId');
            $transactionKey = config('services.authorize.transactionKey');
            $endPoint = null;
            if (App::environment() !== 'production') //Not ideal but it's where they stored it.
                $endPoint = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
            else
                $endPoint = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
            return new CardPaymentManager($loginId, $transactionKey, $endPoint, $app['CardPaymentCustomerProfile']);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [CardPaymentManager::class];
    }
}
