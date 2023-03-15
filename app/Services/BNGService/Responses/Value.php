<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

abstract class Value
{
    protected $data = [];
    protected ResponseData $responseData;

    /**
     * @param ResponseData $responseData
     */
    public function __construct(ResponseData $responseData)
    {
        $this->data = $responseData->getData();
        $this->responseData = $responseData;
    }

    /**
     * @return array|\Psr\Http\Message\ResponseInterface|null
     */
    public function getData(): array|\Psr\Http\Message\ResponseInterface|null
    {
        return $this->data;
    }
}