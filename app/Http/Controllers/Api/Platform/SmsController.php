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
        $result = resolve('forus.services.sms_notification')->sendSms(
            trans('sms.messages.' . $request->input('type')),
            $request->input('phone')
        );

        return $result ? response(null, 200)
            : response()->json(['errors' => ['phone' => [trans('sms.failed')]]], 422);
    }
}
