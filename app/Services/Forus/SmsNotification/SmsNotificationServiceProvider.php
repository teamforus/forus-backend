<?php

namespace App\Services\Forus\SmsNotification;

use Illuminate\Support\ServiceProvider;

/**
 * Class SmsNotificationServiceProvider
 * @package App\Services\Forus\SmsNotification
 */
class SmsNotificationServiceProvider extends ServiceProvider
{
    /**
     *
     */
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
        $this->app->singleton('forus.services.sms_notification', function () {
            return new SmsService();
        });
    }
}