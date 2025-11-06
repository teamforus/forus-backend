<?php

namespace App\Searches;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Scopes\Builders\FundRequestQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestSearch extends BaseSearch
{
    protected ?Employee $employee = null;

    /**
     * @param array $filters
     * @param Builder|Relation|FundRequest $builder
     */
    public function __construct(array $filters, Builder|Relation|FundRequest $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|FundRequest
     */
    public function query(): Builder|Relation|FundRequest
    {
        /** @var Builder|Relation|FundRequest $builder */
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
            $this->getFilter('archived')
                ? $builder->whereIn('state', FundRequest::STATES_ARCHIVED)
                : $builder->whereNotIn('state', FundRequest::STATES_ARCHIVED);
        }

        if ($this->hasFilter('from') && $from = $this->getFilter('from')) {
            $builder->where('created_at', '>=', $from);
        }

        if ($this->hasFilter('to') && $to = $this->getFilter('to')) {
            $builder->where('created_at', '<=', $to);
        }

        if ($employee_id = $this->getFilter('employee_id')) {
            $builder->where('employee_id', $employee_id);
        }

        if ($this->hasFilter('assigned') && $this->getFilter('assigned')) {
            $builder->whereHas('employee');
        }

        if ($this->hasFilter('assigned') && !$this->getFilter('assigned')) {
            $builder->whereDoesntHave('employee');
        }

        if ($this->getFilter('identity_id')) {
            $builder->where('identity_id', $this->getFilter('identity_id'));
        }

        return $this->order($builder);
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param string|null $orderBy
     * @return Builder|Relation|FundRequest
     */
    public function appendSortableFields(
        Builder|Relation|FundRequest $builder,
        ?string $orderBy
    ): Builder|Relation|FundRequest {
        $subQuery = match($orderBy) {
            'fund_name' => Fund::query()
                ->whereColumn('id', 'fund_requests.fund_id')
                ->select('name')
                ->limit(1),
            'assignee_email' => IdentityEmail::query()
                ->whereHas('identity', function (Builder|Identity $builder) {
                    $builder->whereHas('employees', function (Builder|Employee $builder) {
                        $builder->where('organization_id', $this->employee->organization_id);
                        $builder->whereColumn('id', 'fund_requests.employee_id');
                    });
                })
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
                ->whereRelation('fund_request_clarifications', function (Builder $builder) {
                    $builder->whereNull('answered_at');
                })
                ->selectRaw('count(*)'),
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

    /**
     * @param Builder|Relation|FundRequest $builder
     * @return Builder|Relation|FundRequest
     */
    protected function order(Builder|Relation|FundRequest $builder): Builder|Relation|FundRequest
    {
        $orderBy = $this->getFilter('order_by', 'created_at');
        $orderDir = $this->getFilter('order_dir', 'desc');

        $builder = $this->appendSortableFields($builder, $orderBy);
        $builder = FundRequest::query()->fromSub($builder, 'fund_requests');

        return $builder->orderBy($orderBy, $orderDir)->latest();
    }
}
