<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\Payment\PaymentSubscriptionManager;
use Error;
use Illuminate\Support\ServiceProvider;


class PaymentSubscriptionServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (!config()->has('app.process_automated_payments'))
            throw new Error('Process Automated Payments setting not set in configuration.');

        $this->app->singleton(PaymentSubscriptionManager::class, function($app) {
            $muck = $app->make(MuckConnection::class);
            $processSubscriptionPayments = config('app.process_automated_payments');
            return new PaymentSubscriptionManager($muck, $processSubscriptionPayments);
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
        return [
            PaymentSubscriptionManager::class,
        ];
    }
}
