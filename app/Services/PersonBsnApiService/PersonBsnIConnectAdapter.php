<?php

namespace App\Services\PersonBsnApiService;

use App\Services\IConnectApiService\IConnect;
use App\Services\PersonBsnApiService\Interfaces\PersonBsnApiInterface;
use App\Services\PersonBsnApiService\Interfaces\PersonInterface;
use Throwable;

class PersonBsnIConnectAdapter implements PersonBsnApiInterface
{
    public function __construct(protected Iconnect $iConnect)
    {
    }

    /**
     * @param string $bsn
     * @param array $with
     * @param array $fields
     * @throws Throwable
     * @return PersonInterface|null
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?PersonInterface
    {
        return $this->iConnect->getPerson($bsn, $with, $fields);
    }
}
