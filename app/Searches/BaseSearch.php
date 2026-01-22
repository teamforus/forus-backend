<?php

namespace App\Searches;

use App\Http\Requests\BaseFormRequest;
use App\Models\BaseModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseSearch
{
    protected array $filters;
    protected BaseModel|Builder|Relation|null $builder;

    /**
     * @param array $filters
     * @param BaseModel|Builder|Relation|null $builder
     */
    public function __construct(array $filters, BaseModel|Builder|Relation|null $builder = null)
    {
        $this->filters = $filters;
        $this->builder = $builder ? clone $builder : null;
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
     * @param BaseModel|Builder|Relation $builder
     * @noinspection PhpUnused
     */
    public function setBuilder(BaseModel|Builder|Relation $builder): void
    {
        $this->builder = $builder;
    }

    /**
     * @return BaseModel|Builder|Relation
     * @noinspection PhpUnused
     */
    public function getBuilder(): BaseModel|Builder|Relation
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
     * @return Builder|Relation|BaseModel|null
     */
    public function query(): Builder|Relation|BaseModel|null
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
