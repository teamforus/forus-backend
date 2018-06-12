<?php

namespace App\Services\ApiRequestService;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;

class ApiRequest
{
    public function post($url, $body = [])
    {
        $httpClient = new GuzzleClient();

        return $httpClient->post( $url, [
            RequestOptions::JSON => $body
        ]);
    }
}