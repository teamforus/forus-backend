<?php

namespace App\Services\Forus\Notification;

use App\Services\Forus\Notification\Commands\NotificationsTokensImportCommand;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Repositories\NotificationRepo;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
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