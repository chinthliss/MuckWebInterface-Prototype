<?php

namespace App\Providers;

use App\CardPaymentManager;
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
        $this->app->singleton(CardPaymentManager::class, function($app) {
            $loginId = config('services.authorize.loginId');
            $transactionKey = config('services.authorize.transactionKey');
            return new CardPaymentManager($loginId, $transactionKey);
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
