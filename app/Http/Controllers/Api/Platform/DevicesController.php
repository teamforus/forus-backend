<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\DeleteDevicePushRequest;
use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Http\Responses\NoContentResponse;
use App\Models\Implementation;
use App\Services\Forus\Notification\NotificationService;
use Exception;

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
     * Register identity push notification token.
     *
     * @param RegisterDevicePushRequest $request
     * @throws Exception
     * @return NoContentResponse
     */
    public function registerPush(RegisterDevicePushRequest $request): NoContentResponse
    {
        $ios = match ($request->client_type()) {
            Implementation::ME_APP_REQUESTER_IOS, Implementation::ME_APP_PROVIDER_IOS => true,
            Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_PROVIDER_ANDROID => false,
            default => str_contains($request->server('HTTP_USER_AGENT'), 'iOS'),
        };
        $type = $ios ? NotificationService::TYPE_PUSH_IOS : NotificationService::TYPE_PUSH_ANDROID;
        $token = $request->input('id');

        $this->mailNotification->storeNotificationToken($request->auth_address(), $token, $type);

        return new NoContentResponse();
    }

    /**
     * Delete identity push notification token.
     *
     * @param DeleteDevicePushRequest $request
     * @throws Exception
     * @return NoContentResponse
     */
    public function deletePush(DeleteDevicePushRequest $request): NoContentResponse
    {
        $token = $request->input('id');

        $this->mailNotification->removeNotificationToken($token, null, $request->auth_address());

        return new NoContentResponse();
    }
}
