<?php


namespace App\Searches;


use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\FundRequestRecord;
use App\Models\IdentityEmail;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use Illuminate\Database\Eloquent\Builder;

class FundRequestSearch extends BaseSearch
{
    protected ?Employee $employee = null;

    /**
     * @param array $filters
     * @param Builder|null $builder
     */
    public function __construct(array $filters, Builder $builder = null)
    {
        parent::__construct($filters, $builder ?: FundRequest::query());
    }

    /**
     * @return FundRequest|Builder
     */
    public function query(): ?Builder
    {
        /** @var FundRequest|Builder $builder */
        $builder = parent::query();

        if ($this->employee) {
            FundRequestQuery::whereEmployeeIsValidatorOrSupervisor($builder, $this->employee);
        }

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            FundRequestQuery::whereQueryFilter($builder, $q);
        }

        if ($this->hasFilter('fund_id') && $fundId = $this->getFilter('fund_id')) {
            $builder->where('fund_id', $fundId);
        }

        if ($this->hasFilter('state') && $state = $this->getFilter('state')) {
            $builder->where('state', $state);
        }

        if ($this->hasFilter('archived')) {
            $states = [
                FundRequest::STATE_DECLINED,
                FundRequest::STATE_DISREGARDED,
            ];

            $this->getFilter('archived')
                ? $builder->whereIn('state', $states)
                : $builder->whereNotIn('state', $states);
        }

        if ($this->hasFilter('from') && $from = $this->getFilter('from')) {
            $builder->where('created_at', '>=', $from);
        }

        if ($this->hasFilter('to') && $to = $this->getFilter('to')) {
            $builder->where('created_at', '<=', $to);
        }

        if ($employee_id = $this->getFilter('employee_id')) {
            $employee = Employee::find($employee_id);

            $builder->whereHas('records', static function(Builder $builder) use ($employee) {
                FundRequestRecordQuery::whereEmployeeIsAssignedValidator($builder, $employee);
            });
        }

        if ($this->hasFilter('assigned') && $this->getFilter('assigned')) {
            $builder->whereHas('records.employee');
        }

        if ($this->hasFilter('assigned') && !$this->getFilter('assigned')) {
            $builder->whereDoesntHave('records.employee');
        }

        return $this->order($builder);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function order(Builder $builder): Builder
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);
        $builder = FundRequest::query()->fromSub($builder, 'fund_requests');

        return $builder->orderBy($orderBy, $orderDir)->latest();
    }

    /**
     * @param Builder|FundRequest $builder
     * @param string|null $orderBy
     * @return Builder|FundRequest
     */
    public function appendSortableFields(
        Builder|FundRequest $builder,
        ?string $orderBy
    ): Builder|FundRequest {
        $subQuery = match($orderBy) {
            'fund_name' => Fund::query()
                ->whereColumn('id', 'fund_requests.fund_id')
                ->select('name')
                ->limit(1),
            'assignee_email' => IdentityEmail::query()
                ->whereHas('identity.employees', function(Builder $builder) {
                    $builder->where('organization_id', $this->employee->organization_id);
                    $builder->whereHas('fund_request_records', function(Builder $builder) {
                        $builder->whereColumn('fund_request_id', 'fund_requests.id');
                    });
                })
                ->where('verified', true)
                ->where('primary', true)
                ->select('email')
                ->limit(1),
            'requester_email' => IdentityEmail::query()
                ->whereColumn('identity_address', 'fund_requests.identity_address')
                ->where('verified', true)
                ->where('primary', true)
                ->select('email')
                ->limit(1),
            'no_answer_clarification' => FundRequestRecord::query()
                ->whereColumn('fund_request_id', 'fund_requests.id')
                ->whereRelation(
                    'fund_request_clarifications',
                    fn(Builder $q) => $q->whereNull('answered_at')
                )
                ->selectRaw('COUNT(*)'),
            default => null,
        };

        return $builder->addSelect($subQuery ? [
            $orderBy => $subQuery,
        ] : []);
    }

    /**
     * @param Employee $employee
     * @return FundRequestSearch
     */
    public function setEmployee(Employee $employee): static
    {
        $this->employee = $employee;

        return $this;
    }
}