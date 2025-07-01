<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PrevalidationsExport extends BaseFieldedExport
{
    protected static string $transKey = 'prevalidations';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'code',
        'used',
        'records',
    ];

    /**
     * @param array $fields
     * @param Builder|Relation $query
     */
    public function __construct(
        protected array $fields,
        protected Builder|Relation $query,
    ) {
        $this->data = $this->export($query);
    }

    /**
     * @param Builder|Relation $query
     * @return Collection
     */
    public function export(Builder|Relation $query): Collection
    {
        $query = $query->with([
            'prevalidation_records.record_type.translations',
        ]);

        (clone $query)->update([
            'exported' => true,
        ]);

        return $this->exportTransform($query->get());
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function (Prevalidation $prevalidation) use ($fieldLabels) {
            $row = array_only($this->getRow($prevalidation), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $records = in_array('records', $this->fields) ? static::getRecords($prevalidation) : [];

            return [...$row, ...$records];
        })->values();
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected function getRow(Prevalidation $prevalidation): array
    {
        return [
            'code' => $prevalidation->uid,
            'used' => trans('export.prevalidations.used_' . ($prevalidation->state === Prevalidation::STATE_USED ? 'yes' : 'no')),
        ];
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected static function getRecords(Prevalidation $prevalidation): array
    {
        return $prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
            return !Str::endsWith($record->record_type->key, '_eligible');
        })->pluck('value', 'record_type.name')->toArray();
    }
}
