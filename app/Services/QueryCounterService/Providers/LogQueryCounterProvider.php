<?php

namespace App\Services\QueryCounterService\Providers;

use App\Services\QueryCounterService\Contracts\QueryCounterLogContract;
use App\Services\QueryCounterService\Data\QueryCounter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class LogQueryCounterProvider extends QueryCounterLogContract
{
    /**
     * @param QueryCounter $queryCounter
     * @return void
     */
    public function log(QueryCounter $queryCounter): void
    {
        $channel = Arr::get($this->config, 'channel', 'daily');

        Log::channel($channel)->debug('Max queries per route exceeded:', [
            'route_name' => $queryCounter->getRouteName(),
            'url' => $queryCounter->getRequest()->url(),
            'query' => $queryCounter->getRequest()->query(),
            'locale' => Lang::getLocale(),
            'sql_queries_time' => $queryCounter->getQueriesTime(),
            'sql_queries_count' => $queryCounter->getQueriesCount(),
        ]);
    }
}
