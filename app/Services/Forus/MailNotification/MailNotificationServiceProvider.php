<?php

namespace App\Services\Forus\MailNotification;

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
    }
}