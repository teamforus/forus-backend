<?php

namespace App\Services\ApiRequestService;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;

class ApiRequest
{
    /**
     * @param $url
     * @param array $body
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($url, $body = [], $headers = [])
    {
        $httpClient = new GuzzleClient();

        return $httpClient->post($url, [
            'http_errors' => false,
            RequestOptions::JSON => $body,
            'headers' => $headers
        ]);
    }

    /**
     * @param $url
     * @param array $body
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($url, $body = [], $headers = [])
    {
        $httpClient = new GuzzleClient();

        return $httpClient->get($url, [
            'http_errors' => false,
            RequestOptions::QUERY => $body,
            'headers' => $headers
        ]);
    }
}