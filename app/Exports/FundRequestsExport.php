<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Searches\FundRequestSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FundRequestsExport extends BaseFieldedExport
{
    protected static string $transKey = 'fund_requests';

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
     * FundRequestsExport constructor.
     *
     * @param IndexFundRequestsRequest $request
     * @param Employee $employee
     * @param array $fields
     */
    public function __construct(IndexFundRequestsRequest $request, Employee $employee, protected array $fields)
    {
        $this->data = $this->export($request, $employee);
    }

    /**
     * @param IndexFundRequestsRequest $request
     * @param Employee $employee
     * @return Collection
     */
    protected function export(IndexFundRequestsRequest $request, Employee $employee): Collection
    {
        $search = (new FundRequestSearch($request->only([
            'q', 'state', 'employee_id', 'from', 'to', 'order_by', 'order_dir', 'assigned',
        ])))->setEmployee($employee);

        return $this->exportTransform($search->query());
    }

    /**
     * @param Builder $builder
     * @return Collection
     */
    protected function exportTransform(Builder $builder): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        $fundRequests = (clone $builder)->with([
            'identity.record_bsn',
            'records',
            'fund',
        ])->get();

        $recordKeyList = FundRequestRecord::query()
            ->whereIn('fund_request_id', (clone $builder)->select('id'))
            ->pluck('record_type_key');

        return $fundRequests->map(function (FundRequest $request) use ($recordKeyList, $fieldLabels) {
            $row = array_only($this->getRow($request), $this->fields);

            $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $row[$key],
            ]), []);

            $records = (array) $recordKeyList->reduce(fn ($records, $key) => [
                ...$records, $key => $request->records->firstWhere('record_type_key', $key),
            ], []);

            $requestRecords = in_array('records', $this->fields) ? static::getRecords($records) : [];

            return [...$row, ...$requestRecords];
        })->values();
    }

    /**
     * @param FundRequest $request
     * @return array
     */
    protected function getRow(FundRequest $request): array
    {
        return [
            'bsn' => $request->identity?->record_bsn?->value ?: '-',
            'fund_name' => $request->fund->name,
            'status' => trans("export.fund_requests.state-values.$request->state"),
            'validator' => $request->employee?->identity?->email ?: '-',
            'created_at' => $request->created_at,
            'resolved_at' => $request->resolved_at,
            'lead_time_days' => (string) $request->lead_time_days,
            'lead_time_locale' => $request->lead_time_locale,
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
