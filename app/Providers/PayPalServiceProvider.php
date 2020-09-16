<?php

namespace App\Providers;

use Error;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPal\PayPalManager;
use App\Payment\CardPaymentManager;
use Illuminate\Support\ServiceProvider;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PayPalServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PayPalManager::class, function ($app) {
            if (!config()->has('services.paypal'))
                throw new Error('Paypal settings are missing.');

            $config = config('services.paypal');

            if (!$config->has('account'))
                throw new Error('Paypal account not set in configuration.');
            $account = $config['account'];

            if (!$config->has('clientId'))
                throw new Error('Paypal client_id not set in configuration.');
            $clientId = $config['clientId'];

            if (!$config->has('secret'))
                throw new Error('Paypal secret not set in configuration.');
            $secret = $config['secret'];

            if (!$config->has('subscriptionId'))
                throw new Error('Paypal subscriptionId not set in configuration.');
            $subscriptionId = $config[''];

            if ($app->environment('production'))
                $environment = new ProductionEnvironment($clientId, $secret);
            else
                $environment = new SandboxEnvironment($clientId, $secret);

            $paymentManager = $app->make(PaymentTransactionManager::class);

            return new PayPalManager($account, $environment, $paymentManager, $subscriptionId);
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
