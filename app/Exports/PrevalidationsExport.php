<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PrevalidationsExport extends BaseExport
{
    protected static string $transKey = 'prevalidations';
    protected Collection $recordTypeList;

    protected const string DYNAMIC_FIELD_RECORDS = 'records';
    protected const array DYNAMIC_FIELDS_KEYS = [self::DYNAMIC_FIELD_RECORDS];

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
     * @param Builder|Relation|Prevalidation $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|Prevalidation $builder, array $fields)
    {
        $this->recordTypeList = $this->makeRecordTypeList($builder, $fields);

        parent::__construct($builder, $fields);
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
        })->pluck('value', 'record_type.key')->toArray();
    }

    /**
     * @param string $fieldKey
     * @return array
     */
    protected function getDynamicColumnDefinitionsFor(string $fieldKey): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_RECORDS || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        return $this->recordTypeList->map(fn ($label, string $key) => [
            'key' => static::makeDynamicColumnKey($key, 'prevalidation_record'),
            'label' => $label,
        ])->values()->all();
    }

    /**
     * @param string $fieldKey
     * @param Model|Prevalidation $model
     * @return array
     */
    protected function getDynamicRowValuesFor(string $fieldKey, Model|Prevalidation $model): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_RECORDS || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        return collect(static::getRecords($model))->mapWithKeys(fn (string $value, string $key) => [
            static::makeDynamicColumnKey($key, 'prevalidation_record') => $value,
        ])->all();
    }

    /**
     * @param Builder|Relation|Prevalidation $builder
     * @param array $fields
     * @return Collection
     */
    protected function makeRecordTypeList(Builder|Relation|Prevalidation $builder, array $fields): Collection
    {
        if (!in_array(static::DYNAMIC_FIELD_RECORDS, $fields, true)) {
            return collect();
        }

        return PrevalidationRecord::query()
            ->whereIn('prevalidation_id', (clone $builder)->select('id'))
            ->with('record_type.translations')
            ->get()
            ->filter(fn (PrevalidationRecord $record) => !Str::endsWith($record->record_type->key, '_eligible'))
            ->mapWithKeys(fn (PrevalidationRecord $record) => [
                $record->record_type->key => trim((string) $record->record_type->name) !== ''
                    ? $record->record_type->name
                    : $record->record_type->key,
            ]);
    }
}
