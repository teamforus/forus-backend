<?php

namespace App\Services\ProductboardApiService;

use GuzzleHttp\Client;

/**
 * Class ProductboardApi
 * @package App\Services\ProductboardApiService
 */
class ProductboardApi
{
    protected $api_url = "https://api.productboard.com/";
    protected $api_key;

    /**
     * ProductboardApi constructor.
     */
    public function __construct()
    {
        $this->api_key = config('forus.productboard_api.key');
    }

    /**
     * Make request headers
     *
     * @return string[]
     */
    public function makeRequestHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Make Guzzle request options
     *
     * @param string $method
     * @param array $data
     * @return array
     */
    protected function makeRequestOptions(string $method, array $data): array {
        return array_merge([
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => config('forus.productboard_api.connect_timeout', 10),
        ], $method === 'GET' ? [
            'query' => $data,
        ]: [
            'json' => $data,
        ]);
    }

    /**
     * Make the request to the API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    public function request(string $method, string $url, array $data = []): array
    {
        $guzzleClient = new Client();

        try {
            $options = $this->makeRequestOptions($method, $data);
            $response = $guzzleClient->request($method, $url, $options);

            return [
                'success' => true,
                'response_code'  => $response->getStatusCode(),
                'response_body' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'response_code' => $e->getCode(),
                'response_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function storeNote(array $data): array
    {
        return $this->request('POST', $this->api_url. 'notes', $data);
    }
}
