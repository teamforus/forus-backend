<?php

namespace App\Services\BNGService\Data;

use Psr\Http\Message\ResponseInterface;

class ResponseData
{
    protected $data;
    protected int $code;
    protected array $headers;

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
     * @return array
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}