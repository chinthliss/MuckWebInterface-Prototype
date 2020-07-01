<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


class PaymentTransactionServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentTransactionServiceProvider::class, function($app) {
            $muck = $app->make('MuckConnection');
            return new PaymentTransactionServiceProvider($muck);
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
        return [PaymentTransactionServiceProvider::class];
    }
}
