<?php

namespace App\Services\BNGService\Responses;

class BulkPaymentValue extends Value {
    /**
     * @return string
     */
    public function getTransactionStatus(): ?string
    {
        return $this->data['transactionStatus'] ?? null;
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getNumberOfTransactions(): ?int
    {
        return $this->data['numberOfTransactions'] ?? null;
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getRequestedExecutionDate(): ?int
    {
        return $this->data['requestedExecutionDate'] ?? null;
    }

    /**
     * @return string
     */
    public function getStatus(): ?string
    {
        switch ($this->getTransactionStatus()) {
            case 'RCVD':
            case 'PDNG':
            case 'PATC': return 'pending';
            case 'RJCT': return 'rejected';
            case 'ACTC': return 'accepted';
            default: return null;
        }
    }
}