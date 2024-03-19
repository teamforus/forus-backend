<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Share\ShareEmailRequest;
use App\Http\Requests\Api\Platform\Share\ShareSmsRequest;
use App\Mail\Share\ShareAppMail;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\SmsNotification\SmsService;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;

/**
 * Class ShareController
 * @package App\Http\Controllers\Api\Platform
 */
class ShareController extends Controller
{
    use ThrottleWithMeta;

    protected $maxAttempts = 3;
    protected $decayMinutes = 1;

    protected $smsService;
    protected $notificationService;
}
