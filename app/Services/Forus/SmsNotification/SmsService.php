<?php

namespace App\Services\Forus\SmsNotification;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class SmsService
{
    /**
     * @param string $body
     * @param string $phoneNumber
     * @return bool
     */
    public function sendSms(string $body, string $phoneNumber): bool
    {
        if (Config::get('forus.twilio.debug', false)) {
            Log::debug(json_encode(compact('body', 'phoneNumber'), 128));
            return true;
        }

        $sid = Config::get('forus.twilio.sid');
        $token = Config::get('forus.twilio.token');

        if (!$sid || $token) {
            return false;
        }

        try {
            (new Client($sid, $token))->messages->create($phoneNumber, [
                'from' => Config::get('forus.twilio.from'),
                'body' => $body,
            ]);

            return true;
        } catch (\Throwable $e) {
            logger()->error('Error during sms sending: ' . $e->getMessage());
            return false;
        }
    }
}