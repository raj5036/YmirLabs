<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CaseUnlock::class,
        Commands\UserCreate::class,
        Commands\CreatePromoCode::class,
        Commands\NotifyUserAdd::class,
        Commands\NotifyUserRemove::class,
        Commands\NotifyNewCases::class,
        Commands\NotifyDeadline::class,
        Commands\NotifyDeadlineFollowup::class,
        Commands\StbExport::class,
        Commands\CreateOsloUser::class,
        Commands\SendNPSEmail::class,
        Commands\SendMailRetain::class,
        
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Unlock cases that have been locked by a physician for more than 30 minutes
        $schedule->command('case:unlock')->everyMinute();

        // Deadline notification checks if any cases has a deadline within 2 hours
        $schedule->command('notify:deadline')->hourly();

        // Deadline notification checks if any cases has a deadline within 2 hours
        $schedule->command('notify:deadline-followup')->hourly();

        // New cases notification checks if there are any new cases submitted within the last 4 hours
        $schedule->command('notify:new-cases')
            ->cron('0 */8 * * *') // Every four hours - 00:00, 08:00, 16:00
            ->timezone(env('APP_TIMEZONE')); // Lets skip the one at 04:00 to let people sleep

        // Send NPS emails to new customers who completed the examination 3 days ago.
        // $file = 'command_output.log';
        $schedule->command('send:nps-email')->dailyAt('14:00');
        // ->sendOutputTo($file);

         // Send retain emails to new customers who hasn't completed the examination the last day.
        $schedule->command('send:retain-email')->dailyAt('14:00');
         
    }
}
