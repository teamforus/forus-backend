<?php

namespace App\Services\QueryCounterService\Data;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;

class QueryCounter
{
    /**
     * Constructor.
     *
     * @param array $queries Array of QueryExecuted events.
     * @param Request $request The current HTTP request.
     * @param QueryConfig $config
     */
    public function __construct(
        protected array $queries,
        protected Request $request,
        protected QueryConfig $config,
    ) {}

    /**
     * @return QueryConfig
     */
    public function getConfig(): QueryConfig
    {
        return $this->config;
    }

    /**
     * Add a QueryExecuted event to the queries array.
     *
     * @param QueryExecuted $query
     * @return void
     */
    public function addQuery(QueryExecuted $query): void
    {
        $this->queries[] = $query;
    }

    /**
     * Calculate the total query execution time.
     *
     * @return float Total execution time in milliseconds.
     */
    public function getQueriesTime(): float
    {
        return array_sum(array_map(fn($query) => $query->time, $this->queries));
    }

    /**
     * Get the request.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the total number of queries executed.
     *
     * @return int
     */
    public function getQueriesCount(): int
    {
        return count($this->queries);
    }

    /**
     * Get the route name.
     *
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return optional($this->request->route())?->getName() ?? null;
    }

    /**
     * @return bool
     */
    public function shouldLog(): bool
    {
        // Check if the feature is enabled
        if (!$this->config->isEnabled()) {
            return false;
        }

        // Check if the route is excluded from logging
        if ($this->isExcludedRoute()) {
            return false;
        }

        // Check if the conditions meet the logging criteria
        return $this->isQueriesCountExceeded() || $this->isQueriesTimeExceeded();
    }

    /**
     * Check if the current route is excluded from logging.
     *
     * @return bool
     */
    protected function isExcludedRoute(): bool
    {
        return in_array($this->getRouteName(), $this->config->getExcludedRoutes());
    }

    /**
     * Get the minimum number of queries for a specific route.
     *
     * @param ?string $routeName
     * @return int
     */
    protected function getMinQueriesForRoute(?string $routeName): int
    {
        $minQueriesOverwrite = $this->config->getMinQueriesOverwrite();
        return $minQueriesOverwrite[$routeName] ?? $this->config->getMinQueries();
    }

    /**
     * Get the minimum query time for a specific route.
     *
     * @param ?string $routeName
     * @return int
     */
    protected function getMinQueriesTimeForRoute(?string $routeName): int
    {
        $minQueriesTimeOverwrite = $this->config->getMinQueriesTimeOverwrite();
        return $minQueriesTimeOverwrite[$routeName] ?? $this->config->getMinQueriesTime();
    }

    /**
     * Check if the query count has exceeded the threshold.
     *
     * @return bool
     */
    protected function isQueriesCountExceeded(): bool
    {
        $routeName = $this->getRouteName();
        $minQueries = $this->getMinQueriesForRoute($routeName);

        return $this->getQueriesCount() >= $minQueries;
    }

    /**
     * Check if the query time has exceeded the threshold.
     *
     * @return bool
     */
    protected function isQueriesTimeExceeded(): bool
    {
        $routeName = $this->getRouteName();
        $minQueriesTime = $this->getMinQueriesTimeForRoute($routeName);

        return $this->getQueriesTime() >= $minQueriesTime;
    }

}
