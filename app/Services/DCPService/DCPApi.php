<?php

namespace App\Services\DCPService;

use App\Services\DCPService\Responses\DCPResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DCPApi
{
    /**
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct(
        protected string $url,
        protected string $username,
        protected string $password,
    ) {
    }

    /**
     * @param array $payload
     * @return DCPResponse
     */
    public function createOrder(array $payload): DCPResponse
    {
        return $this->request('post', '/api/Orders', $payload);
    }

    /**
     * @param string $orderIdDcp
     * @return DCPResponse
     */
    public function getOrder(string $orderIdDcp): DCPResponse
    {
        return $this->request('get', '/api/Orders/' . rawurlencode($orderIdDcp));
    }

    /**
     * @param string $orderIdDcp
     * @return DCPResponse
     */
    public function deleteOrder(string $orderIdDcp): DCPResponse
    {
        return $this->request('delete', '/api/Orders/' . rawurlencode($orderIdDcp));
    }

    /**
     * @param string $message
     * @param Throwable|null $e
     * @return void
     */
    public static function logError(string $message, ?Throwable $e = null): void
    {
        Log::channel('dcp')->error(implode("\n", array_filter([
            $message,
            $e?->getMessage(),
            $e?->getTraceAsString(),
        ])));
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return DCPResponse
     */
    protected function request(string $method, string $endpoint, array $data = []): DCPResponse
    {
        try {
            $response = $this->http()->{$method}($endpoint, $data);

            if ($response->failed()) {
                self::logError(sprintf(
                    'DCP %s %s failed with status %s: %s',
                    strtoupper($method),
                    $endpoint,
                    $response->status(),
                    $response->body(),
                ));
            }

            return new DCPResponse($response);
        } catch (Throwable $e) {
            self::logError(sprintf('DCP %s %s failed.', strtoupper($method), $endpoint), $e);

            return new DCPResponse(null, $e);
        }
    }

    /**
     * @return PendingRequest
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->withBasicAuth($this->username, $this->password)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout((int) Config::get('dcp.timeout', 30));
    }
}
