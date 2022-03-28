<?php

namespace App\Services\BNGService\Data;

class AuthData
{
    protected $url;
    protected $params;

    /**
     * @param string $url
     * @param array $params
     */
    public function __construct(string $url, array $params = [])
    {
        $this->url = $url;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return implode("?", [$this->url, http_build_query($this->getParams())]);
    }

    /**
     * @return string[]
     */
    public function getParams(): array
    {
        return $this->params;
    }
}