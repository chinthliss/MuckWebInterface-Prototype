<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\Payment\PaymentTransactionItemCatalogue;
use App\Payment\PaymentTransactionManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;


class PaymentTransactionServiceProvider extends ServiceProvider implements DeferrableProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentTransactionManager::class, function($app) {
            $muck = $app->make(MuckConnection::class);
            return new PaymentTransactionManager($muck);
        });
        $this->app->singleton(PaymentTransactionItemCatalogue::class, function($app) {
            return new PaymentTransactionItemCatalogue();
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
            PaymentTransactionManager::class,
            PaymentTransactionItemCatalogue::class
        ];
    }
}
