<?php

namespace App\Providers;

use App\Payment\PatreonManager;
use Error;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PatreonServiceProvider extends ServiceProvider implements DeferrableProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PatreonManager::class, function ($app) {
            if (!config()->has('services.patreon'))
                throw new Error('Patreon settings are missing.');

            $config = config('services.patreon');

            if (!array_key_exists('clientId', $config))
                throw new Error('Patreon Client ID not set in configuration.');
            $clientId = $config['clientId'];

            if (!array_key_exists('clientSecret', $config))
                throw new Error('Patreon Client Secret not set in configuration.');
            $clientSecret = $config['clientSecret'];

            if (!array_key_exists('creatorAccessToken', $config))
                throw new Error('Patreon Creator Access Token not set in configuration.');
            $creatorAccessToken = $config['creatorAccessToken'];

            if (!array_key_exists('creatorRefreshToken', $config))
                throw new Error('Patreon Creator Refresh Token not set in configuration.');
            $creatorRefreshToken = $config['creatorRefreshToken'];

            if (!array_key_exists('campaigns', $config))
                throw new Error('Patreon Campaigns not set in configuration.');
            $campaigns = $config['campaigns'];

            if (!config()->has('app.process_automated_payments'))
                throw new Error('Process Automated Payments setting not set in configuration.');
            $processRewards = config('app.process_automated_payments');

            return new PatreonManager($clientId, $clientSecret,
                $creatorAccessToken, $creatorRefreshToken, $campaigns, $processRewards);
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
        return [PatreonManager::class];
    }
}
