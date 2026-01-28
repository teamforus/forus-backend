<?php

namespace App\Services\QueryCounterService\Providers;

use App\Services\QueryCounterService\Contracts\QueryCounterLogContract;
use App\Services\QueryCounterService\Data\QueryCounter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class DatabaseQueryCounterProvider extends QueryCounterLogContract
{
    /**
     * @param QueryCounter $queryCounter
     * @return void
     */
    public function log(QueryCounter $queryCounter): void
    {
        $connection = Arr::get($this->config, 'connection');
        $table = Arr::get($this->config, 'table', 'query_counter_logs');
        $group = Arr::get($this->config, 'group', 'default');

        DB::connection($connection)->table($table)->insert([
            'group' => $group,
            'route_name' => $queryCounter->getRouteName(),
            'url' => $queryCounter->getRequest()->url(),
            'query' => $this->encodeQuery($queryCounter->getRequest()->query()),
            'locale' => Lang::getLocale(),
            'sql_queries_time' => $queryCounter->getQueriesTime(),
            'sql_queries_count' => $queryCounter->getQueriesCount(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array $query
     * @return string|null
     */
    protected function encodeQuery(array $query): ?string
    {
        $encoded = json_encode($query);

        return $encoded === false ? null : $encoded;
    }
}
