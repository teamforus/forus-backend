<?php


namespace App\Services\BunqService\Endpoint;


use bunq\Http\ApiClient;
use bunq\Model\Generated\Endpoint\BunqMeTab;

class BunqMeTabB extends BunqMeTab
{
    public static function checkStatus(
        int $bunqMeTabId,
        int $monetaryAccountId = null,
        array $customHeaders = []
    ) {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $bunqMeTabId]
            ),
            [],
            $customHeaders
        );

        $response = json_decode($responseRaw->getBodyString(), true);
        $response = $response[self::FIELD_RESPONSE][self::INDEX_FIRST];
        $result_inquiries = $response['BunqMeTab']['result_inquiries'];

        return [
            'status' => $response['BunqMeTab']['status'],
            'amount_paid' => collect($result_inquiries)->sum(
                'payment.Payment.amount.value'
            )
        ];
    }
}