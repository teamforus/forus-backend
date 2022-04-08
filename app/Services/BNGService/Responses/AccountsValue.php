<?php

namespace App\Services\BNGService\Responses;

class AccountsValue extends Value
{
    /**
     * @return array|null
     */
    public function getAccounts(): ?array
    {
        return $this->data['accounts'] ?? null;
    }
}