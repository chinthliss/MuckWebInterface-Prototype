<?php

namespace App\Providers;

use App\Muck\MuckConnection;
use App\DatabaseForMuckUserProvider;
use App\Muck\MuckObjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class DatabaseForMuckAuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('accounts', function($app, array $config) {
            $muckConnection = $app->make(MuckConnection::class);
            $muckObjectService = $app->make(MuckObjectService::class);
            return new DatabaseForMuckUserProvider($muckConnection, $muckObjectService);
        });
    }
}
