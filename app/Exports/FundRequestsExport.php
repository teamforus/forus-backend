<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class FundRequestsExport extends BaseExport
{
    protected static string $transKey = 'fund_requests';
    protected Collection $recordKeyList;

    protected const string DYNAMIC_FIELD_RECORDS = 'records';
    protected const array DYNAMIC_FIELDS_KEYS = [self::DYNAMIC_FIELD_RECORDS];

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'bsn',
        'fund_name',
        'status',
        'validator',
        'created_at',
        'resolved_at',
        'lead_time_days',
        'lead_time_locale',
        'records',
    ];

    /**
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'identity.record_bsn',
        'employee.identity.primary_email',
        'records',
        'fund',
    ];

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|FundRequest $builder, array $fields)
    {
        $this->recordKeyList = in_array(static::DYNAMIC_FIELD_RECORDS, $fields, true)
            ? FundRequestRecord::query()
                ->whereIn('fund_request_id', (clone $builder)->select('id'))
                ->pluck('record_type_key')
                ->unique()
                ->values()
            : collect();

        parent::__construct($builder, $fields);
    }

    /**
     * @param Model|FundRequest $model
     * @return array
     */
    protected function getRow(Model|FundRequest $model): array
    {
        return [
            'bsn' => $model->identity?->record_bsn?->value ?: '-',
            'fund_name' => $model->fund->name,
            'status' => trans("export.fund_requests.state-values.$model->state"),
            'validator' => $model->employee?->identity?->email ?: '-',
            'created_at' => $model->created_at,
            'resolved_at' => $model->resolved_at,
            'lead_time_days' => (string) $model->lead_time_days,
            'lead_time_locale' => $model->lead_time_locale,
        ];
    }

    /**
     * @param Collection|array $records
     * @return array|string[]
     */
    protected static function getRecords(Collection|array $records): array
    {
        return array_map(fn (FundRequestRecord|null $record = null) => $record?->value ?: '-', $records);
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

        return $this->recordKeyList->map(fn (string $key) => [
            'key' => static::makeDynamicColumnKey($key, 'fund_request_record'),
            'label' => $key,
        ])->all();
    }

    /**
     * @param string $fieldKey
     * @param Model|FundRequest $model
     * @return array
     */
    protected function getDynamicRowValuesFor(string $fieldKey, Model|FundRequest $model): array
    {
        if ($fieldKey !== static::DYNAMIC_FIELD_RECORDS || !$this->shouldExpandDynamicField($fieldKey)) {
            return [];
        }

        $records = $this->recordKeyList->mapWithKeys(fn (string $key) => [
            $key => $model->records->firstWhere('record_type_key', $key),
        ])->all();

        $records = static::getRecords($records);

        return $this->recordKeyList->mapWithKeys(fn (string $key) => [
            static::makeDynamicColumnKey($key, 'fund_request_record') => $records[$key] ?? null,
        ])->all();
    }
}
