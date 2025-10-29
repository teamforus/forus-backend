<?php

namespace App\Exports\Base;

use App\Exports\Traits\FormatsExportedData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BaseExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    use FormatsExportedData;

    protected Collection $data;
    protected static string $transKey = '';
    protected static array $exportFields = [];

    protected array $builderWithArray = [];
    protected array $builderWithCountArray = [];

    protected array $totals = [];

    /**
     * @param Builder|Relation|Model $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|Model $builder, protected array $fields = [])
    {
        $this->data = $this->export($builder);
        $this->appendTotals();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $collection = $this->collection();

        return $collection->isNotEmpty()
            ? array_keys($collection->first())
            : array_map(fn ($key) => static::trans($key), $this->fields);
    }

    /**
     * @return array
     */
    public static function getExportFields(): array
    {
        return array_reduce(static::$exportFields, fn ($list, $key) => array_merge($list, [[
            'key' => $key,
            'name' => static::trans($key),
        ]]), []);
    }

    /**
     * @return array
     */
    public static function getExportFieldsRaw(): array
    {
        return static::$exportFields;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function trans(string $key): ?string
    {
        $transKey = static::$transKey;

        return trans("export.$transKey.$key");
    }

    /**
     * @param Builder|Relation $builder
     * @return Collection
     */
    protected function export(Builder|Relation $builder): Collection
    {
        return $this->exportTransform(
            $builder
                ->with($this->getBuilderWithArray())
                ->withCount($this->getBuilderWithCountArray())
                ->get()
        );
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys(
            $data->map(fn (Model $model) => array_only($this->getRow($model), $this->fields))
        );
    }

    /**
     * @param Model $model
     * @return array
     */
    abstract protected function getRow(Model $model): array;

    /**
     * @return array
     */
    protected function getBuilderWithArray(): array
    {
        return $this->builderWithArray;
    }

    /**
     * @return array
     */
    protected function getBuilderWithCountArray(): array
    {
        return $this->builderWithCountArray;
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function transformKeys(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function ($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }

    /**
     * @return array|array[]
     */
    protected function getTotals(): array
    {
        return [];
    }

    /**
     * @return void
     */
    protected function appendTotals(): void
    {
        if (count($this->totals)) {
            $this->data->push($this->getTotals());
        }
    }

    /**
     * @param Model $model
     * @param array $attributes
     * @return void
     */
    protected function accumulateTotals(Model $model, array $attributes = [])
    {

    }
}
