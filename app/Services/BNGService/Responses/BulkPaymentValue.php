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
     * @return null|string
     *
     * @psalm-return 'accepted'|'pending'|'rejected'|null
     */
    public function getStatus(): string|null
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