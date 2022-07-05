<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;
use App\Services\BNGService\Responses\Pagination\PaginationLinks;

class TransactionsValue extends Value
{
    /**
     * @return array|null
     */
    public function getTransactions(): ?array
    {
        return $this->data['transactions'] ?? null;
    }

    /**
     * @return TransactionValue[]|null
     */
    public function getTransactionsBooked(): ?array
    {
        $transactions = $this->getTransactions()['booked'] ?? null;

        return is_array($transactions) ? array_map(function(array $transaction) {
            return new TransactionValue(new ResponseData($transaction));
        }, $transactions) : null;
    }

    /**
     * @return PaginationLinks
     */
    public function getTransactionsLinks(): PaginationLinks
    {
        return new PaginationLinks(new ResponseData($this->getTransactions()['_links']));
    }

    /**
     * @return array|null
     */
    public function getAccount(): ?array
    {
        return $this->data['transactions'] ?? null;
    }

    /**
     * @return array|null
     * @noinspection PhpUnused
     */
    public function getAccountIban(): ?string
    {
        return $this->getAccount()['iban'] ?? null;
    }
}