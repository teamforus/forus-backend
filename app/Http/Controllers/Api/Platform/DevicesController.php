<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\DeleteDevicePushRequest;
use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Http\Controllers\Controller;
use App\Services\Forus\Notification\NotificationService;

/**
 * Class DevicesController
 * @property NotificationService $mailNotification
 * @package App\Http\Controllers\Api\Platform
 */
class DevicesController extends Controller
{
    private $mailNotification;

    public function __construct()
    {
        $this->mailNotification = resolve(
            'forus.services.notification'
        );
    }

    /**
     * Register identity push notification token
     *
     * @param RegisterDevicePushRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function registerPush(
        RegisterDevicePushRequest $request
    ) {
        $ios = strpos($request->server('HTTP_USER_AGENT'), 'iOS') !== FALSE;

        $this->mailNotification->storeNotificationToken(
            auth_address(),
            $ios ? $this->mailNotification::TYPE_PUSH_IOS:
                $this->mailNotification::TYPE_PUSH_ANDROID,
            $request->input('id')
        );

        return response(null, 201);
    }

    /**
     * Delete identity push notification token
     *
     * @param DeleteDevicePushRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function deletePush(
        DeleteDevicePushRequest $request
    ) {
        $this->mailNotification->removeNotificationToken(
            $request->input('id'),
            null,
            auth_address()
        );

        return response(null);
    }
}
