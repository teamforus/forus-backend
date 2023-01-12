<?php


namespace App\Searches;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseSearch
{
    protected array $filters;
    protected Builder|null $builder;

    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        $this->filters = $filters;
        $this->builder = clone $builder;
    }

    /**
     * @param array $filters
     * @noinspection PhpUnused
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param Builder $builder
     * @noinspection PhpUnused
     */
    public function setBuilder(Builder $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * @return Builder
     * @noinspection PhpUnused
     */
    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasFilter(string $key): bool
    {
        return array_has($this->filters, $key);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getFilter(string $key, $default = null): mixed
    {
        return array_get($this->filters, $key, $default);
    }

    /**
     * @param string $key
     * @param string $format
     * @return Carbon|null
     */
    public function getFilterDate(string $key, string $format = 'Y-m-d'): ?Carbon
    {
        if ($this->hasFilter($key)) {
            return Carbon::createFromFormat($format, $this->getFilter($key));
        }

        return null;
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        return $this->getBuilder();
    }

    /**
     * @param string[] $columns
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws Exception
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }
}