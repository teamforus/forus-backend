<?php

namespace App\Services\Forus\SmsNotification\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class SmsNotificationService
 * @package App\Services\Forus\SmsNotification\Facades
 */
class SmsNotificationService extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'forus.services.sms_notification';
    }
}
