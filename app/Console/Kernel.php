<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //Clear up payment records that were never accepted
         $schedule->command('payment:closepending')->everyTenMinutes();

        //Update Patreon Records
        $schedule->command('patreon:update')->twiceDaily();

        //Since these should only be done in the environment handling rewarding things
        if (config('process_payment_subscriptions')) {
            $schedule->command('payment:processsubscriptions')->hourly();
            $schedule->command('patreon:processrewards')->twiceDaily();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
