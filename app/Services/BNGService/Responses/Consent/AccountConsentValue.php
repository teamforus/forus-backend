<?php

namespace App\Services\BNGService\Responses\Consent;

class AccountConsentValue extends ConsentValue
{
    /**
     * @param string $authRedirectUri
     * @param string $redirectToken
     * @return string
     */
    protected function makeRedirectUri(string $authRedirectUri, string $redirectToken): string
    {
        return implode('/', [$authRedirectUri, 'bank-connections', $redirectToken]);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return "AIS:" . $this->getConsentId();
    }

    /**
     * @return string
     */
    public function getBulkInitiationPaymentId(): string
    {
        return $this->data['paymentInitiationBatchGroupId'];
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->data['paymentId'];
    }

    /**
     * @return string
     */
    public function getConsentStatus(): ?string
    {
        return $this->data['consentStatus'] ?? null;
    }
    /**
     * @return string
     */
    public function getConsentId(): ?string
    {
        return $this->data['consentId'] ?? null;
    }
}