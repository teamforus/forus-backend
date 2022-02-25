<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

class Balances extends Value
{
    public const BALANCE_TYPE_EXPECTED = 'Expected';
    public const BALANCE_TYPE_CLOSING_BOOKING = 'ClosingBooked';

    /**
     * @param string|null $type
     * @return array|null
     */
    public function getBalances(string $type = null): ?array
    {
        $balances = $this->data['balances'] ?? [];

        return array_filter($balances, function(array $balance) use ($type) {
            return !$type || $balance['balanceType'] === $type;
        });
    }

    /**
     * @return TransactionValue[]|null
     * @noinspection PhpUnused
     */
    public function getExpectedBalance(): ?BalanceValue
    {
        $balance = $this->getBalances(static::BALANCE_TYPE_EXPECTED)[0] ?? null;

        return $balance ? new BalanceValue(new ResponseData($balance)) : null;
    }

    /**
     * @return TransactionValue[]|null
     * @noinspection PhpUnused
     */
    public function getClosingBookedBalance(): ?BalanceValue
    {
        $balance = $this->getBalances(static::BALANCE_TYPE_CLOSING_BOOKING)[0] ?? null;

        return $balance ? new BalanceValue(new ResponseData($balance)) : null;
    }

    /**
     * @return array|null
     */
    public function getAccount(): ?array
    {
        return $this->data['account'] ?? null;
    }

    /**
     * @return array|null
     * @noinspection PhpUnused
     */
    public function getAccountIban(): ?string
    {
        return $this->getAccount()['iban'] ?? null;
    }

    /**
     * @return array|null
     * @noinspection PhpUnused
     */
    public function getAccountCurrency(): ?string
    {
        return $this->getAccount()['currency'] ?? null;
    }
}