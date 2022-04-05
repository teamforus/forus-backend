<?php

namespace App\Services\BNGService\Data;

use Psr\Http\Message\ResponseInterface;

class ResponseData
{
    protected $data;
    protected $code;
    protected $headers;

    /**
     * @param string|ResponseInterface $data
     * @param int $code
     * @param array $headers
     */
    public function __construct($data, int $code = 200, array $headers = [])
    {
        if ($data instanceof ResponseInterface) {
            $headers = $data->getHeaders();
            $code = $data->getStatusCode();
            $data = $data->getBody()->getContents();
        }

        $this->code = $code;
        $this->data = is_string($data) ? json_decode($data, true) : $data;
        $this->headers = $headers;
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        return in_array($this->code, [200, 201]);
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
        return $this->code;
    }

    /**
     * @return array
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @param array|\string[][] $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}