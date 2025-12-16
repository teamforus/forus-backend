<?php

namespace App\Services\IConnectApiService\Responses;


use Illuminate\Http\Client\Response;

class ResponseData
{
    protected Response $response;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
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
     * @return array|null
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
