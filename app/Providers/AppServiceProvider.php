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
        Blade::if('Admin', function() {
            $user = auth()->user();
            return $user && $user->hasRole('admin');
        });

        // Add the ability to have a customizable header on each page
        Blade::if('SiteNotice', function(){
            $filePath = public_path('site-notice.txt');
            return file_exists($filePath);
        });

        Blade::directive('SiteNoticeContent', function() {
            $filePath = public_path('site-notice.txt');
            return implode('<br/>', file($filePath, FILE_IGNORE_NEW_LINES));
        });
    }
}
