<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;
use Psr\Http\Message\ResponseInterface;

abstract class Value
{
    protected mixed $data = [];
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
     * @return ResponseInterface|array|null
     */
    public function getData(): ResponseInterface|array|null
    {
        return $this->data;
    }
}
