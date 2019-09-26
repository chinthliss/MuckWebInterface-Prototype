<?php

namespace App\Providers;

use App\DatabaseForMuckSessionHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\ConnectionInterface;

use Session;
use Config;

class DatabaseForMuckSessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(ConnectionInterface $connection)
    {
        Session::extend('databaseformuck', function($app) use ($connection) {
            return new DatabaseForMuckSessionHandler(
                $connection,
                Config::get('session.table'),
                Config::get('session.lifetime'),
                $app
            );
        });
    }
}
