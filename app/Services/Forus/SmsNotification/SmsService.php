<?php

namespace App\Services\Forus\SmsNotification;

use Twilio\Rest\Client;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class SmsService
{
    /**
     * @param string $message
     * @param string $phoneNumber
     * @return bool
     */
    public function sendSms(
        string $message,
        string $phoneNumber
    ): bool {
        try {
            $client = new Client(
                config('forus.twilio.sid'),
                config('forus.twilio.token')
            );

            $client->messages->create($phoneNumber, [
                'from' => config('forus.twilio.from'),
                'body' => $message,
            ]);

            return true;
        } catch (\Exception $exception) {
            logger()->error(sprintf(
                'Error during sms sending: %s',
                $exception->getMessage()
            ));

            return false;
        }
    }
}