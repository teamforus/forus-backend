<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\RegisterDevicePushRequest;
use App\Http\Controllers\Controller;

class DevicesController extends Controller
{
    /**
     * RegisterDevicePushRequest
     *
     * @param RegisterDevicePushRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function registerPush(
        RegisterDevicePushRequest $request
    ) {
        $mailNotification = resolve('forus.services.mail_notification');

        $mailNotification->addConnection(
            auth()->user()->getAuthIdentifier(),
            $mailNotification::TYPE_PUSH_MESSAGE,
            $request->input('id')
        );

        return response(null, 201);
    }
}
