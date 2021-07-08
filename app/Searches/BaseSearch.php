<?php


namespace App\Searches;


use Illuminate\Database\Eloquent\Builder;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class WebshopSearch
 * @package App\Searches
 */
class BaseSearch
{
    protected $filters;
    protected $builder;

    /**
     * WebshopSearch constructor.
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        $this->filters = $filters;
        $this->builder = $builder;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param Builder $builder
     */
    public function setBuilder(Builder $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * @return Builder
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
    public function getFilter(string $key, $default = null)
    {
        return array_get($this->filters, $key, $default);
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
    public function get($columns = ['*']): Collection
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