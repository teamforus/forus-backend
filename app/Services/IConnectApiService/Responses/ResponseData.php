<?php

namespace App\Services\IConnectApiService\Responses;

use Psr\Http\Message\ResponseInterface;

class ResponseData
{
    protected ResponseInterface $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        return in_array($this->getCode(), [200, 201]);
    }

    /**
     * @return bool
     */
    public function error(): bool
    {
        return !$this->success();
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return array
     */
    public function getData(): ?array
    {
        return json_decode($this->response->getBody()->getContents(), true);
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }
}