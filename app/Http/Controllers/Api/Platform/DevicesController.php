<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\DeleteDevicePushRequest;
use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Http\Controllers\Controller;

/**
 * Class DevicesController
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

        $this->mailNotification->addConnection(
            auth()->id(),
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
        $this->mailNotification->deleteConnection(
            auth()->id(),
            $request->input('id')
        );

        return response(null, 200);
    }
}
