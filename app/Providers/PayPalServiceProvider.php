<?php

namespace App\Providers;

use Error;
use App\Payment\PaymentTransactionManager;
use App\Payment\PayPalManager;
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

            if (!array_key_exists('account', $config))
                throw new Error('Paypal account not set in configuration.');
            $account = $config['account'];

            if (!array_key_exists('clientId', $config))
                throw new Error('Paypal client_id not set in configuration.');
            $clientId = $config['clientId'];

            if (!array_key_exists('secret', $config))
                throw new Error('Paypal secret not set in configuration.');
            $secret = $config['secret'];

            if (!array_key_exists('subscriptionId', $config))
                throw new Error('Paypal subscriptionId not set in configuration.');
            $subscriptionId = $config['subscriptionId'];

            if ($app->environment('production'))
                $environment = new ProductionEnvironment($clientId, $secret);
            else
                $environment = new SandboxEnvironment($clientId, $secret);

            if (!config()->has('app.process_automated_payments'))
                throw new Error('Process Automated Payments setting not set in configuration.');
            $processSubscriptions = config('app.process_automated_payments');

            return new PayPalManager($account, $environment, $subscriptionId, $processSubscriptions);
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
