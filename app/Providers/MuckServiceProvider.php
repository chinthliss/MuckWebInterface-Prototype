<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\Muck\FakeMuckConnection;
use App\Muck\HttpMuckConnection;
use App\Muck\MuckObjectService;
use App\Muck\MuckObjectsProviderViaDatabase;
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
        $connection = null;

        if (!config()->has('muck'))
            throw new Error('Muck configuration not set.');

        $config = config('muck');
        $driver = $config['driver'];
        if ($driver == 'fake') $connection = new FakeMuckConnection($config);
        if ($driver == 'http') $connection = new HttpMuckConnection($config);
        if (!$driver) throw new Error('Unrecognized muck driver: ' . $driver);

        $provider = new MuckObjectsProviderViaDatabase();

        $this->app->singleton(MuckConnection::class, function($app) use ($connection) {
            return $connection;
        });

        $this->app->singleton(MuckObjectService::class, function($app) use ($connection, $provider) {
            return new MuckObjectService($connection, $provider);
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
        return [MuckConnection::class, MuckObjectService::class];
    }
}
