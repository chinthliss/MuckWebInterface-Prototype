<?php

namespace App\Providers;

use App\User as User;
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
        // Add the capability of blade views to tell if user is Game Staff
        Blade::if('Staff', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->hasRole('staff');
        });

        // Add the capability of blade views to tell if user is Site Admin
        Blade::if('Admin', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->hasRole('admin');
        });

        Blade::if('Character', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->getCharacter();
        });

        // Add the capability of blade views to pick up on fullwidth preference
        Blade::if('PrefersFullWidth', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->getPrefersFullWidth();
        });

        // Add the capability of blade views to pick up on avatar hiding preference
        Blade::if('PrefersNoAvatars', function() {
            /** @var User $user */
            $user = auth()->user();
            return $user && $user->getPrefersNoAvatars();
        });

        // Add the ability to have a customizable header on each page
        Blade::if('SiteNoticeExists', function(){
            $filePath = public_path('site-notice.txt');
            return file_exists($filePath);
        });
    }
}
