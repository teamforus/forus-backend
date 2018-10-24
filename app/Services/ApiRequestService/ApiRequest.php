<?php

namespace App\Services\ApiRequestService;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;

class ApiRequest
{
    /**
     * @param $url
     * @param array $body
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($url, $body = [])
    {
        $httpClient = new GuzzleClient();

        return $httpClient->post($url, [
            'http_errors' => false,
            RequestOptions::JSON => $body
        ]);
    }

    /**
     * @param $url
     * @param array $body
     */
    public function get($url, $body = [])
    {
        $httpClient = new GuzzleClient();

        $httpClient->get($url, [
            'http_errors' => false,
            RequestOptions::QUERY => $body
        ]);
    }
}