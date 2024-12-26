<?php

namespace App\Services\Forus\SmsNotification\Facades;

use Illuminate\Support\Facades\Facade;

class SmsNotificationService extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'forus.services.sms_notification';
    }
}
