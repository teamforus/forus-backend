<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Http\Controllers\Controller;

/**
 * Class DevicesController
 * @package App\Http\Controllers\Api\Platform
 */
class DevicesController extends Controller
{
    /**
     * Register identity push notification token
     *
     * @param RegisterDevicePushRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function registerPush(
        RegisterDevicePushRequest $request
    ) {
        $mailNotification = resolve('forus.services.mail_notification');
        $mailNotification->addPushMessageConnection(
            auth()->id(),
            $request->input('id')
        );

        return response(null, 201);
    }
}
