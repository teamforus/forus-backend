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
     *
     * @psalm-return 'forus.services.sms_notification'
     */
    protected static function getFacadeAccessor(): string
    {
        return 'forus.services.sms_notification';
    }
}
