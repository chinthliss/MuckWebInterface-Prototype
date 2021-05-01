<?php

namespace App\Providers;

use App\Payment\PaymentTransactionManager;
use Error;
use App\Payment\CardPaymentManager;
use App\Payment\AuthorizeNetCardPaymentManager;
use App\Payment\FakeCardPaymentManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CardPaymentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (!config()->has('app.card_payment_driver'))
            throw new Error('No card payment driver set');

        $this->app->singleton(CardPaymentManager::class, function($app) {
            $card_payment_driver = config('app.card_payment_driver');
            if ($card_payment_driver == 'authorizenet') {
                $config = config('services.authorizenet');
                return new AuthorizeNetCardPaymentManager($config);
            }
            if ($card_payment_driver == 'fake') {
                return new FakeCardPaymentManager();
            }
            throw new Error('Card payment driver set to unrecognized driver.');
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
