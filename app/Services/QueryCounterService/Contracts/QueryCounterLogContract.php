<?php

namespace App\Services\QueryCounterService\Contracts;

use App\Services\QueryCounterService\Data\QueryCounter;

abstract class QueryCounterLogContract
{
    /**
     * @param array $config
     */
    public function __construct(protected array $config = [])
    {
    }

    /**
     * @param QueryCounter $queryCounter
     * @return void
     */
    abstract public function log(QueryCounter $queryCounter): void;
}
