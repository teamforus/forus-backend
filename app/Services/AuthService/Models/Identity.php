<?php

namespace App\Services\AuthService\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class Identity implements Authenticatable
{
    private $address;
    private $proxyId;
    private $proxyState;

    public function __construct($address, $proxyId, $proxyState)
    {
        $this->address = $address;
        $this->proxyId = $proxyId;
        $this->proxyState = $proxyState;
    }

    public function getAddress() {
        return $this->address;
    }

    public function getProxyId() {
        return $this->proxyId;
    }

    public function getProxyState() {
        return $this->proxyState;
    }

    public function getAuthIdentifier()
    {
        return $this->address;
    }

    public function getAuthIdentifierName()
    {
        return 'address';
    }

    public function getAuthPassword()
    {
        return null;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function getRememberTokenName()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        return null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->address ?: "";
    }
}