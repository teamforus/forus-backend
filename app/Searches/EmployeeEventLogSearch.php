<?php


namespace App\Searches;


use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Voucher;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class EmployeeEventLogSearch extends BaseSearch
{
    protected Employee $employee;
    protected array $permissions;

    /**
     * @var array|string[]
     */
    protected array $organizationRelationMap = [
        'fund' => 'organization',
        'voucher' => 'fund.organization',
        'employee' => 'organization',
        'bank_connection' => 'organization',
    ];

    /**
     * @param Employee $employee
     * @param array $filters
     */
    public function __construct(Employee $employee, array $filters)
    {
        parent::__construct($filters, EventLog::query());

        $this->employee = $employee;
        $this->permissions = Config::get('forus.event_permissions', []);
    }

    /**
     * @return Builder|null
     */
    public function query(): ?Builder
    {
        $builder = parent::query();

        if ($this->hasFilter('q') && $q = $this->getFilter('q')) {
            $builder->whereRelation('identity.primary_email', 'email', 'LIKE', "%$q%");
        }

        $builder->where(function (Builder $builder) {
            $builder->where(fn (Builder $q) => $this->whereVoucherExported($q));
            $builder->orWhere(fn (Builder $q) => $this->whereEvents($q, Voucher::class));
            $builder->orWhere(fn (Builder $q) => $this->whereEvents($q, BankConnection::class));
            $builder->orWhere(fn (Builder $q) => $this->whereEvents($q, Employee::class));
            $builder->orWhere(fn (Builder $q) => $this->whereEvents($q, Fund::class));
        });

        $builder->addSelect([
            'show_private_details' => Employee::whereHas('organization', function (Builder $builder) {
                OrganizationQuery::whereIsEmployee($builder, $this->employee->identity_address);
            })
                ->selectRaw('IF(count(*) > 0, 1, 0)')
                ->whereColumn('event_logs.identity_address', 'employees.identity_address')
                ->limit(1)
        ]);

        if (empty($this->getFilter('loggable', []))) {
            return $builder->whereRaw('FALSE');
        }

        return $builder->orderByDesc('created_at');
    }

    /**
     * @param Builder $builder
     * @param string $morphClass
     * @return void
     */
    protected function whereEvents(Builder $builder, string $morphClass): void
    {
        /** @var Model $morphModel */
        $morphModel = new $morphClass;

        if (!in_array($morphModel->getMorphClass(), $this->getFilter('loggable', []))) {
            return;
        }

        $this->whereLoggableId($builder, $this->getFilter('loggable_id'));
        $this->whereLoggable($builder, $morphModel);
    }

    /**
     * @param Builder $builder
     * @param Model $morphModel
     * @return void
     */
    protected function whereLoggable(Builder $builder, Model $morphModel): void
    {
        $morphKey = $morphModel->getMorphClass();
        $builder->whereIn('event', Arr::get($this->permissions, "$morphKey.events", []));
        $builder->whereHasMorph('loggable', $morphModel::class);

        $builder->whereIn('loggable_id', fn (QBuilder $q) => $q->fromSub(
            $this->makeMorphQuery($morphModel), $morphModel->getTable()
        ));
    }

    /**
     * @param Builder|QBuilder $builder
     * @param int|null $loggable_id
     * @return void
     */
    protected function whereLoggableId(Builder|QBuilder $builder, ?int $loggable_id = null): void
    {
        if ($loggable_id) {
            $builder->whereIn('loggable_id', (array) $loggable_id);
        }
    }

    /**
     * @param Model $model
     * @return Builder|QBuilder
     */
    protected function makeMorphQuery(Model $model): Builder|QBuilder
    {
        $morphKey = $model->getMorphClass();
        $relation = Arr::get($this->organizationRelationMap, $morphKey, '');

        return $model->newQuery()->whereHas($relation, function (Builder $builder) use ($morphKey) {
            OrganizationQuery::whereHasPermissions(
                $builder->where('id', $this->employee->organization_id),
                $this->employee->identity,
                Arr::get($this->permissions, "$morphKey.permissions", [])
            );
        })->select('id');
    }

    /**
     * @param Builder $builder
     * @return void
     */
    function whereVoucherExported(Builder $builder): void
    {
        if (!$this->hasFilter('loggable_id') || $this->hasFilter('loggable' !== 'voucher')) {
            return;
        }

        $builder->where(function (Builder $builder) {
            $builder->where('event', Fund::EVENT_VOUCHERS_EXPORTED);

            $builder->whereHasMorph('loggable', Fund::class, function(Builder $builder) {
                $builder->whereHas('organization', function(Builder $builder) {
                    OrganizationQuery::whereHasPermissions($builder, $this->employee->identity_address, [
                        'manage_vouchers',
                    ]);
                });
            });

            $builder->whereJsonContains(
                'data->fund_export_voucher_ids',
                (int) $this->getFilter('loggable_id')
            );
        });
    }
}