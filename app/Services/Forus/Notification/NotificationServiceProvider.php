<?php

namespace App\Services\Forus\Notification;

use App\Services\Forus\Notification\Commands\NotificationsTokensImportCommand;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->app->booted(function () {
            $schedule = resolve(Schedule::class);

            $schedule->command('forus.notifications:apn-feedback')
                ->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('forus.services.notification', function () {
            return app()->make(NotificationService::class);
        });

        $this->app->bind(INotificationRepo::class, NotificationRepo::class);

        $this->commands([
            NotificationsTokensImportCommand::class
        ]);
    }
}