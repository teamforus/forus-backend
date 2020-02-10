<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SmsRequest;
use App\Http\Controllers\Controller;

/**
 * Class SmsController
 * @package App\Http\Controllers\Api\Platform
 */
class SmsController extends Controller
{
    /**
     * Send sms
     *
     * @param SmsRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function send(
        SmsRequest $request
    ) {
        $phone = $request->input('phone');
        $message = trans('sms.messages.' . $request->input('type'));
        $smsService = resolve('forus.services.sms_notification');

        $result = $smsService->sendSms($message, $phone);

        return $result ? response(null, 200): response()->json([
            'errors' => [
                'phone' => (array) trans('sms.failed')
            ]
        ], 422);
    }
}
