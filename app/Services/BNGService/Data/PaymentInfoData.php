<?php

namespace App\Services\BNGService\Data;

class PaymentInfoData
{
    protected $paymentId;
    protected $redirectToken;
    protected $executionDate;

    /**
     * @param string $paymentId
     * @param string $executionDate
     * @param string|null $redirectToken
     */
    public function __construct(
        string $paymentId,
        string $executionDate,
        string $redirectToken = null
    ) {
        $this->paymentId = $paymentId;
        $this->executionDate = $executionDate;
        $this->redirectToken = $redirectToken;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * @return null|string
     */
    public function getRedirectToken(): string|null
    {
        return $this->redirectToken;
    }

    /**
     * @return string|null
     */
    public function getExecutionDate(): ?string
    {
        return $this->executionDate;
    }
}