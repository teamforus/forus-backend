<?php

namespace App\Exports\Base;

use App\Exports\Traits\FormatsExportedData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
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
    protected const array DYNAMIC_FIELDS_KEYS = [];

    protected array $headings = [];
    protected array $columnKeys = [];
    protected array $resolvedColumns = [];

    protected array $builderWithArray = [];
    protected array $builderWithCountArray = [];

    /**
     * @param Builder|Relation|Model|null $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|Model|null $builder, protected array $fields = [])
    {
        $this->initialize($builder);
    }

    /**
     * @return \Illuminate\Support\Collection|Model[]
     */
    public function collection(): Collection|array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function collectionWithHeadings(): array
    {
        return $this->data
            ->map(fn (array $row) => $this->buildAssociativeRow($row, $this->headings))
            ->toArray();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
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
     * @param Builder|Relation|Model|null $builder
     * @return void
     */
    protected function initialize(Builder|Relation|Model|null $builder): void
    {
        $this->initializeFromRowValues($this->collectRowValues($builder));
    }

    /**
     * @param Collection $rowValues
     * @return void
     */
    protected function initializeFromRowValues(Collection $rowValues): void
    {
        $this->initializeColumnDefinitions($rowValues);

        $data = $this->formatRowValues($rowValues, $this->resolvedColumns);
        $totals = $this->formatTotalsRow();

        $this->data = $totals !== null ? $data->push($totals) : $data;
    }

    /**
     * @param Builder|Relation|Model $builder
     * @return Collection
     */
    protected function collectRowValues(Builder|Relation|Model $builder): Collection
    {
        if ($builder instanceof Model) {
            $builder->loadMissing($this->getBuilderWithArray());

            if (!empty($this->getBuilderWithCountArray())) {
                $builder->loadCount($this->getBuilderWithCountArray());
            }

            return collect([$builder])->map(fn (Model $model) => $this->getRowValues($model))->values();
        }

        return $builder
            ->with($this->getBuilderWithArray())
            ->withCount($this->getBuilderWithCountArray())
            ->get()
            ->map(fn (Model $model) => $this->getRowValues($model))
            ->values();
    }

    /**
     * @return array
     */
    protected function getFieldLabels(): array
    {
        return Arr::pluck(static::getExportFields(), 'name', 'key');
    }

    /**
     * @return array
     */
    protected function getSelectedFieldKeys(): array
    {
        $selectedFields = array_fill_keys($this->fields, true);

        return array_values(array_filter(
            array_keys($this->getFieldLabels()),
            fn (string $key) => array_key_exists($key, $selectedFields),
        ));
    }

    /**
     * @return array
     */
    protected function getBaseFieldKeys(): array
    {
        return $this->getBaseFieldKeysWithout($this->getDynamicFieldKeys());
    }

    /**
     * @param array|string $excludedFields
     * @return array
     */
    protected function getBaseFieldKeysWithout(array|string $excludedFields): array
    {
        $excludedFields = Arr::wrap($excludedFields);

        return array_values(array_filter(
            $this->getSelectedFieldKeys(),
            fn (string $key) => !in_array($key, $excludedFields, true),
        ));
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function hasSelectedField(string $field): bool
    {
        return in_array($field, $this->fields, true);
    }

    /**
     * @return array
     */
    protected function getDynamicFieldKeys(): array
    {
        return static::DYNAMIC_FIELDS_KEYS;
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function isDynamicFieldKey(string $field): bool
    {
        return in_array($field, $this->getDynamicFieldKeys(), true);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function shouldExpandDynamicField(string $field): bool
    {
        return $this->isDynamicFieldKey($field) && $this->hasSelectedField($field);
    }

    /**
     * @param string|int $id
     * @param string $prefix
     * @return string
     */
    protected static function makeDynamicColumnKey(string|int $id, string $prefix): string
    {
        return "{$prefix}_$id";
    }

    /**
     * @return array
     */
    protected function getBaseColumnDefinitions(): array
    {
        $fieldLabels = $this->getFieldLabels();

        return array_map(fn (string $key) => [
            'key' => $key,
            'label' => $fieldLabels[$key] ?? static::trans($key),
        ], $this->getBaseFieldKeys());
    }

    /**
     * @param string $fieldKey
     * @return array
     */
    protected function getDynamicColumnDefinitionsFor(string $fieldKey): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getColumnDefinitions(): array
    {
        $baseColumns = array_column($this->getBaseColumnDefinitions(), null, 'key');

        return array_reduce($this->getSelectedFieldKeys(), function (array $columns, string $field) use ($baseColumns) {
            if ($this->shouldExpandDynamicField($field)) {
                return [...$columns, ...$this->getDynamicColumnDefinitionsFor($field)];
            }

            if (array_key_exists($field, $baseColumns)) {
                $columns[] = $baseColumns[$field];
            }

            return $columns;
        }, []);
    }

    /**
     * @param Collection $rowValues
     * @param array $columns
     * @return array
     */
    protected function filterColumnDefinitions(Collection $rowValues, array $columns): array
    {
        return $columns;
    }

    /**
     * @param string $fieldKey
     * @param Model $model
     * @return array
     */
    protected function getDynamicRowValuesFor(string $fieldKey, Model $model): array
    {
        return [];
    }

    /**
     * @param Model $model
     * @return array
     */
    protected function getRowValues(Model $model): array
    {
        $baseValues = Arr::only($this->getRow($model), $this->getBaseFieldKeys());

        return array_reduce($this->getSelectedFieldKeys(), function (array $rowValues, string $field) use ($model, $baseValues) {
            if ($this->shouldExpandDynamicField($field)) {
                return [...$rowValues, ...$this->getDynamicRowValuesFor($field, $model)];
            }

            if (array_key_exists($field, $baseValues)) {
                $rowValues[$field] = $baseValues[$field];
            }

            return $rowValues;
        }, []);
    }

    /**
     * @param Collection $rowValues
     * @return void
     */
    protected function initializeColumnDefinitions(Collection $rowValues): void
    {
        $columns = $this->filterColumnDefinitions($rowValues, $this->getColumnDefinitions());

        $this->resolvedColumns = $columns;
        $this->columnKeys = array_column($columns, 'key');
        $this->headings = array_column($columns, 'label');
    }

    /**
     * @param Collection $rowValues
     * @param array $columns
     * @return Collection
     */
    protected function formatRowValues(Collection $rowValues, array $columns): Collection
    {
        return $rowValues->map(fn (array $values) => $this->buildRowFromColumns($values, $columns))->values();
    }

    /**
     * @param array $rowValues
     * @param array $columns
     * @return array
     */
    protected function buildRowFromColumns(array $rowValues, array $columns): array
    {
        return array_map(
            fn (array $column) => array_key_exists($column['key'], $rowValues) ? $rowValues[$column['key']] : null,
            $columns,
        );
    }

    /**
     * @param array $rowValues
     * @param array $keys
     * @return array
     */
    protected function buildAssociativeRow(array $rowValues, array $keys): array
    {
        return array_combine($keys, $rowValues);
    }

    /**
     * @return array|null
     */
    protected function getTotalsRowValues(): ?array
    {
        return null;
    }

    /**
     * @return array|null
     */
    protected function formatTotalsRow(): ?array
    {
        $totals = $this->getTotalsRowValues();

        if ($totals !== null) {
            return $this->buildRowFromColumns($totals, $this->resolvedColumns);
        }

        return null;
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
}
