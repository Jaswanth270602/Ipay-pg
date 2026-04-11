<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedulerLog = storage_path('logs/scheduler.log');

        // Pending live payments: poll gateway if callback missing
        $schedule->command('payments:reconcile-pending')
            ->everyTenMinutes()
            ->withoutOverlapping();

        // Pending webhook deliveries (also picks up retries when queue workers were down)
        $schedule->command('webhooks:retry')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->appendOutputTo($schedulerLog);

        // Daily settlement batch — do not use runInBackground() here: it releases the
        // overlap mutex before the child process finishes and can cause double runs.
        $schedule->command('settlements:process-daily')
            ->dailyAt('23:00')
            ->timezone('Asia/Kolkata')
            ->withoutOverlapping(180)
            ->appendOutputTo($schedulerLog);

        // Test settlements: auto-complete after cooling-off (no acquirer dependency)
        $schedule->command('settlements:auto-complete-test')
            ->everyMinute()
            ->withoutOverlapping();

        // Cleanup stale temp/report CSV lifecycle files.
        $schedule->command('csv:cleanup-lifecycle-files')
            ->hourly()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
