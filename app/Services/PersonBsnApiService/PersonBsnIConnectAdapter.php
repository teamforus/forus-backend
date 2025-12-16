<?php

namespace App\Services\PersonBsnApiService;

use App\Services\IConnectApiService\IConnect;
use App\Services\PersonBsnApiService\Interfaces\PersonBsnApiInterface;
use App\Services\PersonBsnApiService\Interfaces\PersonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
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
     * @param bool $cacheResponse
     * @return PersonInterface|null
     * @throws Throwable
     */
    public function getPerson(string $bsn, array $with = [], array $fields = [], bool $cacheResponse = true): ?PersonInterface
    {
        $cacheKey = 'bsn_prefill_data_' . $this->iConnect->getApiOin() . '_' . $bsn;
        $cacheTime = max(Config::get('forus.person_bsn.fund_prefill_cache_time', 60 * 15), 0);
        $shouldCache = $cacheResponse && $cacheTime > 0;

        if ($shouldCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $person = $this->iConnect->getPerson($bsn, $with, $fields);

        if ($shouldCache && $person?->response()?->success()) {
            Cache::put($cacheKey, $person, $cacheTime);
        }

        return $person;
    }
}
