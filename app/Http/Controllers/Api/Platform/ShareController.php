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

    /**
     * ShareController constructor.
     * @param SmsService $smsService
     * @param NotificationService $notificationService
     */
    public function __construct(SmsService $smsService, NotificationService $notificationService)
    {
        $this->smsService = $smsService;
        $this->notificationService = $notificationService;
    }

    /**
     * Send sms
     *
     * @param ShareSmsRequest $request
     * @return JsonResponse
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function sendSms(
        ShareSmsRequest $request
    ): JsonResponse {
        $this->throttleWithKey('to_many_attempts', $request, 'share_app_sms');

        $result = $this->smsService->sendSms(
            trans('share/sms.me_app_download_link.messages'),
            $request->input('phone')
        );

        return $result ? response()->json(): response()->json([
            'errors' => [
                'phone' => (array) trans('share/sms.me_app_download_link.failed')
            ]
        ], 422);
    }

    /**
     * Send sms
     *
     * @param ShareEmailRequest $request
     * @return JsonResponse
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function sendEmail(
        ShareEmailRequest $request
    ): JsonResponse {
        $this->throttleWithKey('to_many_attempts', $request, 'share_app_email');

        $this->notificationService->sendMailNotification(
            $request->input('email'),
            new ShareAppMail()
        );

        return response()->json();
    }
}
