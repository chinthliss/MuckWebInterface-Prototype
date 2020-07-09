<?php

namespace App\Providers;

use App\Payment\PayPalManager;
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
            $config = config('services.paypal');

            $account = $config['account'];
            if (!$account) throw new \Error('Paypal account not set in configuration.');

            $clientId = $config['clientId'];
            if (!$clientId) throw new \Error('Paypal client_id not set in configuration.');

            $secret = $config['secret'];
            if (!$secret) throw new \Error('Paypal secret not set in configuration.');

            if ($app->environment('production'))
                $environment = new ProductionEnvironment($clientId, $secret);
            else
                $environment = new SandboxEnvironment($clientId, $secret);

            return new PayPalManager($environment, $account, $clientId, $secret);
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
