<?php

namespace App\Services\BNGService\Responses;

use App\Services\BNGService\Data\ResponseData;

class Balances extends Value
{
    public const BALANCE_TYPE_EXPECTED = 'Expected';
    public const BALANCE_TYPE_CLOSING_BOOKING = 'ClosingBooked';

    /**
     * @param string|null $type
     *
     * @psalm-return array<never, never>
     */
    public function getBalances(string $type = null): array
    {
        $balances = $this->data['balances'] ?? [];

        return array_filter($balances, function(array $balance) use ($type) {
            return !$type || $balance['balanceType'] === $type;
        });
    }

    /**
     * @noinspection PhpUnused
     */
    public function getClosingBookedBalance(): BalanceValue|null
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
}