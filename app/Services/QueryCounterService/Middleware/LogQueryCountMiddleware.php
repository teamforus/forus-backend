<?php

namespace App\Services\QueryCounterService\Middleware;

use App\Services\QueryCounterService\Data\QueryCounter;
use App\Services\QueryCounterService\QueryCounterService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogQueryCountMiddleware
{
    /**
     * @param QueryCounterService $queryCounter
     */
    public function __construct(
        protected QueryCounterService $queryCounter,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $queryCounter = $this->queryCounter->getQueryCounter($request);

        if ($queryCounter->shouldLog()) {
            $this->log($queryCounter);
        }

        return $response;
    }

    /**
     * @param QueryCounter $queryCounter
     * @return void
     */
    protected function log(QueryCounter $queryCounter): void
    {
        Log::channel($queryCounter->getConfig()->getLogChannel())->debug('Max queries per route exceeded:', [
            'route_name' => $queryCounter->getRouteName(),
            'url' => $queryCounter->getRequest()->url(),
            'query' => $queryCounter->getRequest()->query(),
            'sql_queries_time' => $queryCounter->getQueriesTime(),
            'sql_queries_count' => $queryCounter->getQueriesCount(),
            // 'queries' => $queryCounter->getQueriesCount(),
        ]);
    }
}
