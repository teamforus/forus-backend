<?php

namespace App\Services\Forus\MailNotification\Facades;

use Illuminate\Support\Facades\Facade;

class MailNotificationService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'forus.services.mail_notification';
    }
}
