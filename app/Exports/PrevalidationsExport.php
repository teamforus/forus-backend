<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PrevalidationsExport extends BaseExport
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
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'prevalidation_records.record_type.translations',
    ];

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
     * @param Model|Prevalidation $model
     * @return array
     */
    protected function getRow(Model|Prevalidation $model): array
    {
        return [
            'code' => $model->uid,
            'used' => trans('export.prevalidations.used_' . ($model->state === Prevalidation::STATE_USED ? 'yes' : 'no')),
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
