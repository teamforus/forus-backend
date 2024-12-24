<?php

namespace App\Services\QueryCounterService;

use App\Services\QueryCounterService\Data\QueryConfig;
use App\Services\QueryCounterService\Data\QueryCounter;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class QueryCounterService
{
    /**
     * @var QueryExecuted[]
     */
    protected array $queries = [];

    /**
     * QueryCounterService constructor.
     */
    public function __construct()
    {
        if (Config::get('query-counter.enabled', false)) {
            DB::listen(fn (QueryExecuted $query) => $this->queries[] = $query);
        }
    }

    /**
     * Get the QueryCounter.
     *
     * @param Request $request
     * @return QueryCounter
     */
    public function getQueryCounter(Request $request): QueryCounter
    {
        return new QueryCounter($this->queries, $request, new QueryConfig(
            Config::get('query-counter'),
        ));
    }
}
