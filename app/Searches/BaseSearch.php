<?php

namespace App\Searches;

use App\Http\Requests\BaseFormRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseSearch
{
    protected array $filters;
    protected Model|Builder|Relation|null $builder;

    /**
     * @param array $filters
     * @param Model|Builder|Relation $builder
     */
    public function __construct(array $filters, Model|Builder|Relation $builder)
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
     * @param Model|Builder|Relation $builder
     * @noinspection PhpUnused
     */
    public function setBuilder(Model|Builder|Relation $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * @return Model|Builder|Relation
     * @noinspection PhpUnused
     */
    public function getBuilder(): Model|Builder|Relation
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
     * @return Builder|Relation|Model
     */
    public function query(): Builder|Relation|Model
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
     * @throws Exception
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * @return string[]
     */
    public static function rules(?BaseFormRequest $request = null): array
    {
        return [
            'per_page' => $request->perPageRule(),
        ];
    }
}
