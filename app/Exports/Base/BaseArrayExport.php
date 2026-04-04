<?php

namespace App\Exports\Base;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use LogicException;

abstract class BaseArrayExport extends BaseExport
{
    /**
     * @param array $rows
     * @param array $fields
     */
    public function __construct(protected array $rows, array $fields = [])
    {
        parent::__construct(null, $fields);
    }

    /**
     * @param Builder|Relation|Model|null $builder
     * @return void
     */
    protected function initialize(Builder|Relation|Model|null $builder): void
    {
        $this->initializeFromRowValues($this->collectArrayRowValues($this->rows));
    }

    /**
     * @param array $rows
     * @return Collection
     */
    abstract protected function collectArrayRowValues(array $rows): Collection;

    /**
     * @param Model $model
     * @return array
     */
    protected function getRow(Model $model): array
    {
        throw new LogicException(static::class . ' does not support model-backed row exports.');
    }
}
