<?php

namespace App\Services\PersonBsnApiService\Interfaces;

interface PersonBsnApiInterface
{
    /**
     * @param string $bsn
     * @param array $with
     * @param array $fields
     * @return PersonInterface|null
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?PersonInterface;
}
