<?php

namespace App\Services\BNGService\Responses\Consent;

class BulkPaymentConsentValue extends ConsentValue
{
    /**
     * @param string $authRedirectUri
     * @param string $redirectToken
     * @return string
     */
    protected function makeRedirectUri(string $authRedirectUri, string $redirectToken): string
    {
        return implode('/', [$authRedirectUri, 'payment-bulks', $redirectToken]);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return "PIS:" . $this->getBulkInitiationPaymentId();
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
}