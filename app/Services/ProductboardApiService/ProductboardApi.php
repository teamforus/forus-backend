<?php

namespace App\Services\ProductboardApiService;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class ProductboardApi
{
    protected string $api_url = "https://api.productboard.com/";
    protected string $api_key;
    protected int $connect_timeout;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->api_key = Arr::get($configs, 'access_token');
        $this->connect_timeout = Arr::get($configs, 'connect_timeout', 10);
    }

    /**
     * Make request headers
     *
     * @return string[]
     */
    protected function makeRequestHeaders(): array
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
    protected function makeRequestOptions(string $method, array $data): array
    {
        return array_merge([
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => $this->connect_timeout,
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
    protected function request(string $method, string $url, array $data = []): array
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
    public function create(array $data): array
    {
        return $this->request('POST', $this->api_url . 'notes', $data);
    }
}
