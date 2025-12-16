<?php

namespace App\Services\PersonBsnApiService\Interfaces;

interface PersonBsnApiInterface
{
    /**
     * @param string $bsn
     * @param array $with
     * @param array $fields
     * @param bool $cacheResponse
     * @return PersonInterface|null
     */
    public function getPerson(string $bsn, array $with = [], array $fields = [], bool $cacheResponse = false): ?PersonInterface;
}
