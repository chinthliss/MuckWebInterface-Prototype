<?php

namespace App\Providers;

use App\Muck\MuckObjectService;
use App\SupportTickets\SupportTicketProviderViaDatabase;
use App\SupportTickets\SupportTicketService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SupportTicketServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $muckObjectService = $this->app->make(MuckObjectService::class);
        $provider = new SupportTicketProviderViaDatabase($muckObjectService);
        $this->app->singleton(SupportTicketService::class, function($app) use ($provider) {
            return new SupportTicketService($provider);
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
     * @return array
     */
    public function provides(): array
    {
        return [SupportTicketService::class];
    }
}
