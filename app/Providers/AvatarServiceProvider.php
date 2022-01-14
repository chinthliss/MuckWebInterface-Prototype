<?php

namespace App\Providers;

use App\Avatar\AvatarProvider;
use App\Avatar\AvatarProviderViaDatabase;
use App\Avatar\AvatarService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AvatarServiceProvider extends ServiceProvider  implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $provider = new AvatarProviderViaDatabase();
        $this->app->singleton(AvatarService::class, function($app) use ($provider) {
            return new AvatarService($provider);
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
        return [AvatarService::class, AvatarProvider::class];
    }
}
