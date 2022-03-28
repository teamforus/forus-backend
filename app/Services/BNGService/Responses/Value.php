<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

abstract class Value
{
    protected $data = [];
    protected $responseData;

    /**
     * @param ResponseData $responseData
     */
    public function __construct(ResponseData $responseData)
    {
        $this->data = $responseData->getData();
        $this->responseData = $responseData;
    }
}