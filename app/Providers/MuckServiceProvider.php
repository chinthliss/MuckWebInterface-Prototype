<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\Muck\FakeMuckConnection;
use App\Muck\HttpMuckConnection;
use Error;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;


class MuckServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MuckConnection::class, function($app) {
            if (!config()->has('muck'))
                throw new Error('Muck configuration not set.');

            $config = config('muck');
            $driver = $config['driver'];
            if ($driver == 'fake') return new FakeMuckConnection($config);
            if ($driver == 'http') return new HttpMuckConnection($config);
            throw new Error('Unrecognized muck driver: ' . $driver);
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
        return [MuckConnection::class];
    }
}
