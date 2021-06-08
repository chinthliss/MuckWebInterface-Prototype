<?php

namespace App\Providers;

use App\User;
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
        Blade::if('SiteNotice', function(){
            $filePath = public_path('site-notice.txt');
            return file_exists($filePath);
        });

        Blade::directive('SiteNoticeContent', function() {
            $filePath = public_path('site-notice.txt');
            if (!file_exists($filePath)) return "";
            return implode('<br/>', file($filePath, FILE_IGNORE_NEW_LINES));
        });

        /**
         * Produces a badge with a count of outstanding notifications or returns a blank string
         */
        Blade::directive('AccountNotificationCount', function() {
            /** @var User $user */
            $user = auth()->user();
            if (!$user) return '';
            $count = resolve('App\AccountNotificationManager')->getNotificationCountFor($user);
            return $count ? '<span class="badge badge-light">' . $count . '</span>' : '';
        });
    }
}
