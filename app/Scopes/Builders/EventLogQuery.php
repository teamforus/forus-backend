<?php


namespace App\Scopes\Builders;

use App\Http\Requests\BaseFormRequest;
use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;

class EventLogQuery
{
    /**
     * @param Builder|Relation $query
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(Relation|Builder $query, string $q): Relation|Builder
    {
        return $query->whereRelation('identity.primary_email', 'email', 'LIKE', "%$q%");
    }

    /**
     * @param Relation|Builder $query
     * @param Organization $organization
     * @param BaseFormRequest $request
     * @return Builder|Relation
     */
    public static function queryEvents(
        Relation|Builder $query,
        Organization $organization,
        BaseFormRequest $request
    ): Relation|Builder
    {
        if (!count($request->get('loggable', []))) {
            return $query->where('id', '<', -999);
        }

        $attributes = [
            'loggable' => $request->get('loggable'),
            'loggable_id' => $request->get('loggable_id'),
            'identity' => $request->identity(),
            'entities' => config('forus.event_permissions')
        ];

        return $query->where(function (Builder $builder) use ($organization, $attributes) {
            $builder->where(function (Builder $builder) use ($organization, $attributes) {
                self::queryVoucherEvents($builder, $organization, $attributes);
            })->orWhere(function (Builder $builder) use ($organization, $attributes) {
                self::queryBankConnectionEvents($builder, $organization, $attributes);
            })->orWhere(function (Builder $builder) use ($organization, $attributes) {
                self::queryEmployeeEvents($builder, $organization, $attributes);
            })->orWhere(function (Builder $builder) use ($organization, $attributes) {
                self::queryFundEvents($builder, $organization, $attributes);
            });
        });
    }

    /**
     * @param Builder $query
     * @param Organization $organization
     * @param array $attributes
     * @return void
     */
    private static function queryVoucherEvents(
        Builder $query,
        Organization $organization,
        array $attributes
    ): void {
        if (!in_array('voucher', $attributes['loggable'])) {
            return;
        }

        $relationQuery = Voucher::query()->select('id')
            ->whereHas('fund.organization', function (Builder $builder) use (
                $organization, $attributes
            ) {
                $builder->where('id', $organization->id);
                OrganizationQuery::whereHasPermissions(
                    $builder,
                    $attributes['identity'],
                    $attributes['entities']['voucher']['permissions']
                );
            });

        self::queryByLoggableId($relationQuery, $attributes['loggable_id']);

        $parameters = [
            'events' => $attributes['entities']['voucher']['events'],
            'loggable_class' => Voucher::class,
            'loggable_table' => 'vouchers',
            'relation_query' => $relationQuery
        ];

        $query->where(function (Builder $builder) use ($organization, $parameters) {
            self::queryByLoggable($builder, $organization, $parameters);
        })->orWhere(function (Builder $builder) use ($attributes) {
            if ($attributes['loggable_id']) {
                $builder->where('event', 'vouchers_export');
                $builder->whereHasMorph('loggable', Fund::class);
                $builder->whereJsonContains('data->fund_vouchers_ids', (int)$attributes['loggable_id']);
            }
        });
    }

    /**
     * @param Builder $query
     * @param Organization $organization
     * @param array $attributes
     * @return void
     */
    private static function queryBankConnectionEvents(
        Builder $query,
        Organization $organization,
        array $attributes
    ): void {
        if (!in_array('bank_connection', $attributes['loggable'])) {
            return;
        }

        $relationQuery = BankConnection::query()->select('id')
            ->whereHas('organization', function (Builder $builder) use (
                $organization, $attributes
            ) {
                $builder->where('id', $organization->id);
                OrganizationQuery::whereHasPermissions(
                    $builder,
                    $attributes['identity'],
                    $attributes['entities']['bank_connection']['permissions']
                );
            });

        self::queryByLoggableId($relationQuery, $attributes['loggable_id']);

        $parameters = [
            'events' => $attributes['entities']['bank_connection']['events'],
            'loggable_class' => BankConnection::class,
            'loggable_table' => 'bank_connections',
            'relation_query' => $relationQuery
        ];

        self::queryByLoggable($query, $organization, $parameters);
    }

    /**
     * @param Builder $query
     * @param Organization $organization
     * @param array $attributes
     * @return void
     */
    private static function queryEmployeeEvents(
        Builder $query,
        Organization $organization,
        array $attributes
    ): void {
        if (!in_array('employee', $attributes['loggable'])) {
            return;
        }

        $relationQuery = Employee::query()->select('id')
            ->whereHas('organization', function (Builder $builder) use (
                $organization, $attributes
            ) {
                $builder->where('id', $organization->id);
                OrganizationQuery::whereHasPermissions(
                    $builder,
                    $attributes['identity'],
                    $attributes['entities']['employee']['permissions']
                );
            });

        self::queryByLoggableId($relationQuery, $attributes['loggable_id']);

        $parameters = [
            'events' => $attributes['entities']['employee']['events'],
            'loggable_class' => Employee::class,
            'loggable_table' => 'employees',
            'relation_query' => $relationQuery
        ];

        self::queryByLoggable($query, $organization, $parameters);
    }

    /**
     * @param Builder $query
     * @param Organization $organization
     * @param array $attributes
     * @return void
     */
    private static function queryFundEvents(
        Builder $query,
        Organization $organization,
        array $attributes
    ): void {
        if (!in_array('fund', $attributes['loggable'])) {
            return;
        }

        $relationQuery = Fund::query()->select('id')
            ->whereHas('organization', function (Builder $builder) use (
                $organization, $attributes
            ) {
                $builder->where('id', $organization->id);
                OrganizationQuery::whereHasPermissions(
                    $builder,
                    $attributes['identity'],
                    $attributes['entities']['fund']['permissions']
                );
            });

        self::queryByLoggableId($relationQuery, $attributes['loggable_id']);

        $parameters = [
            'events' => $attributes['entities']['fund']['events'],
            'loggable_class' => Fund::class,
            'loggable_table' => 'funds',
            'relation_query' => $relationQuery
        ];

        self::queryByLoggable($query, $organization, $parameters);
    }


    /**
     * @param Builder $query
     * @param Organization $organization
     * @param array $parameters
     * @return void
     */
    private static function queryByLoggable(
        Builder $query,
        Organization $organization,
        array $parameters
    ): void {
        $query->whereIn('event', $parameters['events']);
        $query->whereHasMorph('loggable', $parameters['loggable_class']);
        $query->where(static function(Builder $builder) use ($organization, $parameters) {
            $builder->whereIn('loggable_id', function (QBuilder $builder) use (
                $organization, $parameters
            ) {
                $builder->fromSub($parameters['relation_query'], $parameters['loggable_table']);
            });
        });
    }

    /**
     * @param Builder|QBuilder $query
     * @param int|null $loggable_id
     * @return void
     */
    private static function queryByLoggableId(Builder|QBuilder $query, ?int $loggable_id = null): void
    {
        if ($loggable_id) {
            $query->whereIn('id', (array) $loggable_id);
        }
    }
}