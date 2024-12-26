<?php

namespace App\Services\BNGService\Responses;

class BulkPaymentValue extends Value {
    /**
     * @return string|null
     */
    public function getTransactionStatus(): ?string
    {
        return $this->data['transactionStatus'] ?? null;
    }

    /**
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getNumberOfTransactions(): ?int
    {
        return $this->data['numberOfTransactions'] ?? null;
    }

    /**
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getRequestedExecutionDate(): ?int
    {
        return $this->data['requestedExecutionDate'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return match ($this->getTransactionStatus()) {
            'RCVD', 'PDNG', 'PATC' => 'pending',
            'RJCT' => 'rejected',
            'ACTC' => 'accepted',
            default => null,
        };
    }
}