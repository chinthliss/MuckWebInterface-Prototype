<?php

namespace App\Providers;

use App\Payment\CardPaymentManager;
use App\Payment\AuthorizeNetCardPaymentManager;
use App\Payment\FakeCardPaymentManager;
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
        $this->app->singleton(CardPaymentManager::class, function($app) {
            $card_payment_driver = config('app.card_payment_driver');
            if ($card_payment_driver == 'authorizenet') {
                $config = config('services.authorizenet');
                return new AuthorizeNetCardPaymentManager($config);
            }
            if ($card_payment_driver == 'fake') {
                return new FakeCardPaymentManager();
            }
            throw new \Error('No card payment driver set');
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
