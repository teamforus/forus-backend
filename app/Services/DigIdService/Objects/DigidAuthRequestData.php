<?php

namespace App\Services\DigIdService\Objects;

class DigidAuthRequestData extends DigidData
{
    protected string $authRedirectUrl;
    protected string $authResolveUrl;
    protected int|string $requestId;

    /**
     * @return string
     */
    public function getAuthRedirectUrl(): string
    {
        return $this->authRedirectUrl;
    }

    /**
     * @param string $authRedirectUrl
     * @return $this
     */
    public function setAuthRedirectUrl(string $authRedirectUrl): self
    {
        return $this->tap(fn() => $this->authRedirectUrl = $authRedirectUrl);
    }

    /**
     * @return string
     */
    public function getAuthResolveUrl(): string
    {
        return $this->authResolveUrl;
    }

    /**
     * @param string $authResolveUrl
     * @return $this
     */
    public function setAuthResolveUrl(string $authResolveUrl): self
    {
        return $this->tap(fn() => $this->authResolveUrl = $authResolveUrl);
    }

    /**
     * @return int|string
     */
    public function getRequestId(): int|string
    {
        return $this->requestId;
    }

    /**
     * @param int|string $requestId
     * @return $this
     */
    public function setRequestId(int|string $requestId): self
    {
        return $this->tap(fn() => $this->requestId = $requestId);
    }
}