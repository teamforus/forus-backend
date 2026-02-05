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
        'records',
        'fund',
    ];

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param array $fields
     */
    public function __construct(Builder|Relation|FundRequest $builder, protected array $fields)
    {
        $this->recordKeyList = FundRequestRecord::query()
            ->whereIn('fund_request_id', (clone $builder)->select('id'))
            ->pluck('record_type_key');

        parent::__construct($builder, $fields);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function (FundRequest $request) use ($fieldLabels) {
            $row = array_only($this->getRow($request), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $records = (array) $this->recordKeyList->reduce(fn ($records, $key) => [
                ...$records, $key => $request->records->firstWhere('record_type_key', $key),
            ], []);

            $requestRecords = in_array('records', $this->fields) ? static::getRecords($records) : [];

            return [...$row, ...$requestRecords];
        })->values();
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
        return array_map(fn (?FundRequestRecord $record = null) => $record?->value ?: '-', $records);
    }
}
