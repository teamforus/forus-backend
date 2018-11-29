<?php

namespace App\Services\Forus\SmsNotification;

use App\Services\ApiRequestService\ApiRequest;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class SmsService
{
    protected $serviceApiUrl;

    /** @var ApiRequest $apiRequest  */
    protected $apiRequest;

    /**
     * MailService constructor.
     */
    public function __construct() {
        $this->apiRequest = app()->make('api_request');
        $this->serviceApiUrl = env('SERVICE_EMAIL_URL', false);
    }

    /**
     * Get endpoint url
     *
     * @param string $uri
     * @param string|null $locale
     * @return string
     */
    private function getEndpoint(
        string $uri,
        string $locale = null
    ) {
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        return $this->serviceApiUrl . '/' . $locale . $uri;
    }


    /**
     * Send sms
     *
     * @param string $title
     * @param string $phone
     * @return bool
     */
    public function sendSms(
        string $title,
        string $phone
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/mobile/sms/', 'nl');

        $res = $this->apiRequest->post($endpoint, [
            'title'   => $title,
            'phone'   => $phone
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending sms to phone %s: %s',
                    $phone,
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }
}