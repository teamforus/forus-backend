<?php

namespace App\Services\BNGService\Responses;

class TransactionValue extends Value
{
    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTransactionId(): string
    {
        return $this->data['transactionId'];
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTransactionAmount(): string
    {
        return $this->data['transactionAmount']['amount'];
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTransactionCurrency(): string
    {
        return $this->data['transactionAmount']['currency'];
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTransactionDescription(): string
    {
        return $this->data['remittanceInformationUnstructured'];
    }
}