<?php


namespace App\Searches;


use App\Models\Employee;
use App\Models\FundRequest;
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
        $builder = parent::query();

        $employee = $this->employee;

        $builder->whereHas('records', function(Builder $builder) use ($employee) {
            FundRequestRecordQuery::whereEmployeeIsValidatorOrSupervisor($builder, $employee);
        });

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            FundRequestQuery::whereQueryFilter($builder, $q);
        }

        if ($this->hasFilter('state') && $state = $this->getFilter('state')) {
            $builder->where('state', $state);
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

        return self::orderRequestsBy(
            $builder,
            $this->getFilter('order_by', 'created_at'),
            $this->getFilter('order_dir', 'desc'),
        );
    }

    /**
     * @param Builder $query
     * @param string $orderBy
     * @param string $orderDir
     * @return Builder
     */
    public static function orderRequestsBy(
        Builder $query,
        string $orderBy,
        string $orderDir,
    ): Builder {
        if ($orderBy == 'fund_name') {
            $query->leftJoin('funds', 'funds.id', 'fund_requests.fund_id')
                ->select('fund_requests.*', 'funds.name as fund_name');
        }

        return $query->orderBy($orderBy, $orderDir)->orderBy('fund_requests.created_at');
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