<?php

namespace App\Searches;

use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Permission;
use App\Models\Voucher;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        'employees' => 'organization',
        'bank_connection' => 'organization',
    ];

    /**
     * @param Employee $employee
     * @param array $filters
     * @param Builder|Relation|EventLog $builder
     */
    public function __construct(Employee $employee, array $filters, Builder|Relation|EventLog $builder)
    {
        parent::__construct($filters, $builder);

        $this->employee = $employee;
        $this->permissions = Config::get('forus.event_permissions', []);
    }

    /**
     * @return Builder|Relation|EventLog
     */
    public function query(): Builder|Relation|EventLog
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

        if (empty($this->getFilter('loggable', []))) {
            return $builder->whereRaw('FALSE');
        }

        return $builder->orderByDesc('created_at');
    }

    /**
     * @param Builder|Relation|EventLog $builder
     * @return void
     */
    public function whereVoucherExported(Builder|Relation|EventLog $builder): void
    {
        if (!$this->hasFilter('loggable_id') || $this->hasFilter('loggable' !== 'voucher')) {
            return;
        }

        $builder->where(function (Builder $builder) {
            $builder->where('event', Fund::EVENT_VOUCHERS_EXPORTED);

            $builder->whereHasMorph('loggable', Fund::class, function (Builder $builder) {
                $builder->whereHas('organization', function (Builder $builder) {
                    OrganizationQuery::whereHasPermissions($builder, $this->employee->identity_address, [
                        Permission::MANAGE_VOUCHERS,
                    ]);
                });
            });

            $builder->whereJsonContains(
                'data->fund_export_voucher_ids',
                (int) $this->getFilter('loggable_id')
            );
        });
    }

    /**
     * @param Builder|Relation|EventLog $builder
     * @param string $morphClass
     * @return void
     */
    protected function whereEvents(Builder|Relation|EventLog $builder, string $morphClass): void
    {
        /** @var Model $morphModel */
        $morphModel = new $morphClass();

        if (!in_array($morphModel->getMorphClass(), $this->getFilter('loggable', []))) {
            return;
        }

        $this->whereLoggableId($builder, $this->getFilter('loggable_id'));
        $this->whereLoggable($builder, $morphModel);
    }

    /**
     * @param Builder|Relation|EventLog $builder
     * @param Model $morphModel
     * @return void
     */
    protected function whereLoggable(Builder|Relation|EventLog $builder, Model $morphModel): void
    {
        $morphKey = $morphModel->getMorphClass();
        $builder->whereIn('event', Arr::get($this->permissions, "$morphKey.events", []));
        $builder->whereHasMorph('loggable', $morphModel::class);

        $builder->whereIn('loggable_id', fn (QBuilder $q) => $q->fromSub(
            $this->makeMorphQuery($morphModel),
            $morphModel->getTable()
        ));
    }

    /**
     * @param Builder|Relation|EventLog|QBuilder $builder
     * @param int|null $loggable_id
     * @return void
     */
    protected function whereLoggableId(Builder|Relation|EventLog|QBuilder $builder, ?int $loggable_id = null): void
    {
        if ($loggable_id) {
            $builder->whereIn('loggable_id', (array) $loggable_id);
        }
    }

    /**
     * @param Model $model
     * @return Builder|Relation|QBuilder
     */
    protected function makeMorphQuery(Model $model): Builder|Relation|QBuilder
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
}
