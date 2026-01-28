<?php

namespace App\Services\QueryCounterService\Middleware;

use App\Services\QueryCounterService\Contracts\QueryCounterLogContract;
use App\Services\QueryCounterService\Data\QueryCounter;
use App\Services\QueryCounterService\QueryCounterService;
use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LogQueryCountMiddleware
{
    /**
     * @param QueryCounterService $queryCounter
     */
    public function __construct(
        protected QueryCounterService $queryCounter,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @throws BindingResolutionException
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
     * @throws BindingResolutionException
     * @return void
     */
    protected function log(QueryCounter $queryCounter): void
    {
        foreach ($this->getProviders($queryCounter) as $providerConfig) {
            if (is_string($providerConfig)) {
                $providerConfig = ['driver' => $providerConfig];
            }

            if (!Arr::get($providerConfig, 'enabled', true)) {
                continue;
            }

            $driver = Arr::get($providerConfig, 'driver');

            if (!$driver || !class_exists($driver) ||
                !is_subclass_of($driver, QueryCounterLogContract::class)) {
                continue;
            }

            $provider = app()->makeWith($driver, ['config' => $providerConfig]);

            if ($provider instanceof QueryCounterLogContract) {
                $provider->log($queryCounter);
            }
        }
    }

    /**
     * @param QueryCounter $queryCounter
     * @return array
     */
    protected function getProviders(QueryCounter $queryCounter): array
    {
        return $queryCounter->getConfig()->getProviders();
    }
}
