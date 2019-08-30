<?php

namespace App\Services\Forus\MailNotification;

use App\Services\Forus\MailNotification\Interfaces\INotificationRepo;
use App\Services\Forus\MailNotification\Repositories\NotificationRepo;
use Illuminate\Support\ServiceProvider;

class MailNotificationServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('forus.services.mail_notification', function () {
            return new MailService();
        });

        $this->app->bind(INotificationRepo::class, NotificationRepo::class);
        $this->app->singleton('forus.services.notifications', function () {
            return app(INotificationRepo::class);
        });
    }
}