<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\DeleteDevicePushRequest;
use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Services\Forus\Notification\NotificationService;
use Exception;
use Illuminate\Http\JsonResponse;

class DevicesController extends Controller
{
    private NotificationService $mailNotification;

    /**
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->mailNotification = $notificationService;
    }

    /**
     * Register identity push notification token
     *
     * @param RegisterDevicePushRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function registerPush(RegisterDevicePushRequest $request): JsonResponse
    {
        $ios = str_contains($request->server('HTTP_USER_AGENT'), 'iOS');
        $type = $ios ? NotificationService::TYPE_PUSH_IOS : NotificationService::TYPE_PUSH_ANDROID;
        $token = $request->input('id');

        $this->mailNotification->storeNotificationToken($request->auth_address(), $token, $type);

        return response()->json([], 201);
    }

    /**
     * Delete identity push notification token
     *
     * @param DeleteDevicePushRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function deletePush(DeleteDevicePushRequest $request): JsonResponse {
        $token = $request->input('id');

        $this->mailNotification->removeNotificationToken($token, null, $request->auth_address());

        return response()->json([], 201);
    }
}
