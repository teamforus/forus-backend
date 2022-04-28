<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

class BulkPaymentTokenValue extends Value
{
    protected $accessToken;

    /**
     * @param ResponseData $data
     */
    public function __construct(ResponseData $data)
    {
        parent::__construct($data);

        $this->accessToken = $this->data['access_token'];
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
}