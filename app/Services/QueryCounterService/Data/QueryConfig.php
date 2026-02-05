<?php

namespace App\Services\QueryCounterService\Data;

use Illuminate\Support\Arr;

class QueryConfig
{
    protected bool $enabled;
    protected ?string $locale;
    protected int $minQueries;
    protected int $minQueriesTime;
    protected array $minQueriesOverwrite;
    protected array $minQueriesTimeOverwrite;
    protected array $excludedRoutes;
    protected array $providers;

    /**
     * @param array $config
     */
    public function __construct(protected array $config)
    {
        $this->locale = Arr::get($this->config, 'locale');
        $this->enabled = Arr::get($this->config, 'enabled', false);
        $this->minQueries = (int) Arr::get($this->config, 'min_queries', 100);
        $this->minQueriesTime = (int) Arr::get($this->config, 'min_queries_time', 100);
        $this->excludedRoutes = Arr::get($this->config, 'excluded_routes', []);
        $this->minQueriesOverwrite = (array) Arr::get($this->config, 'min_queries_overwrite', []);
        $this->minQueriesTimeOverwrite = (array) Arr::get($this->config, 'min_queries_time_overwrite', []);
        $this->providers = (array) Arr::get($this->config, 'providers', []);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get the minimum number of queries to log.
     *
     * @return int
     */
    public function getMinQueries(): int
    {
        return $this->minQueries;
    }

    /**
     * Get the minimum total query execution time (in ms) to log.
     *
     * @return int
     */
    public function getMinQueriesTime(): int
    {
        return $this->minQueriesTime;
    }

    /**
     * Get routes that should be excluded from logging.
     *
     * @return array
     */
    public function getExcludedRoutes(): array
    {
        return $this->excludedRoutes;
    }

    /**
     * Get specific route overrides for minimum query count.
     *
     * @return array
     */
    public function getMinQueriesOverwrite(): array
    {
        return $this->minQueriesOverwrite;
    }

    /**
     * Get specific route overrides for minimum query time.
     *
     * @return array
     */
    public function getMinQueriesTimeOverwrite(): array
    {
        return $this->minQueriesTimeOverwrite;
    }

    /**
     * Get log providers configuration.
     *
     * @return array
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
