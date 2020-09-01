<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Add the capability of blade views to tell if user is an Admin
        Blade::if('admin', function() {
            $user = auth()->user();
            return $user && $user->hasRole('admin');
        });
    }
}
