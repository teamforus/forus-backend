<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

class AccessTokenResponseValue extends Value
{
    protected int $expiresIn;
    protected string $accessToken;

    /**
     * @param ResponseData $data
     */
    public function __construct(ResponseData $data)
    {
        parent::__construct($data);

        $this->expiresIn = $this->data['expires_in'];
        $this->accessToken = $this->data['access_token'];
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }
}