<?php

namespace App\Scopes\Builders;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestQuery
{
    /**
     * @param Builder|Relation|FundRequest $builder
     * @param int $identity_id
     * @return Builder|Relation|FundRequest
     */
    public static function whereApprovedAndVoucherIsActive(
        Builder|Relation|FundRequest $builder,
        int $identity_id,
    ): Builder|Relation|FundRequest {
        return $builder->where(function (Builder $builder) use ($identity_id) {
            $builder->whereHas('fund', static function (Builder $builder) use ($identity_id) {
                $builder->whereHas('vouchers', static function (Builder $builder) use ($identity_id) {
                    $builder->where('identity_id', $identity_id);
                    VoucherQuery::whereNotExpired($builder);
                });
            })->where([
                'state' => FundRequest::STATE_APPROVED,
                'identity_id' => $identity_id,
            ]);
        });
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param int $identity_id
     * @return Builder|Relation|FundRequest
     */
    public static function whereIsPending(
        Builder|Relation|FundRequest $builder,
        int $identity_id,
    ): Builder|Relation|FundRequest {
        return $builder
            ->where('state', FundRequest::STATE_PENDING)
            ->whereRelation('identity', 'id', $identity_id);
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param int $identity_id
     * @return Builder|Relation|FundRequest
     */
    public static function wherePendingOrApprovedAndVoucherIsActive(
        Builder|Relation|FundRequest $builder,
        int $identity_id,
    ): Builder|Relation|FundRequest {
        return $builder->where(function (Builder $builder) use ($identity_id) {
            $builder->where(function (Builder $builder) use ($identity_id) {
                static::whereApprovedAndVoucherIsActive($builder, $identity_id);
            });

            $builder->orWhere(function (Builder $builder) use ($identity_id) {
                static::whereIsPending($builder, $identity_id);
            });
        });
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param string $q
     * @return Builder|Relation|FundRequest
     */
    public static function whereQueryFilter(
        Builder|Relation|FundRequest $builder,
        string $q,
    ): Builder|Relation|FundRequest {
        return $builder->where(function (Builder $query) use ($q) {
            $query->whereHas('fund', static function (Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });

            $query->orWhereHas('identity.primary_email', static function (Builder $builder) use ($q) {
                $builder->where('email', 'LIKE', "%$q%");
            });

            $query->orWhereHas('identity', static function (Builder $builder) use ($q) {
                IdentityQuery::whereBsnLike($builder, $q);
            });

            is_numeric($q) && $query->orWhere('id', '=', $q);
        });
    }

    /**
     * @param Builder|Relation|FundRequest $query
     * @param Employee $employee
     * @return Builder|Relation|FundRequest
     */
    public static function whereEmployeeIsValidatorOrSupervisor(
        Builder|Relation|FundRequest $query,
        Employee $employee,
    ): Builder|Relation|FundRequest {
        return $query->where(static function (Builder $builder) use ($employee) {
            // validator
            $builder->where(fn (Builder $q) => static::whereEmployeeCanBeValidator($q, $employee));

            // supervisor
            $builder->orWhere(fn (Builder $q) => static::whereEmployeeIsValidatorsSupervisor($q, $employee));
        });
    }

    /**
     * @param Builder|Relation|FundRequest $query
     * @param Employee $employee
     * @return Builder|Relation|FundRequest
     */
    public static function whereEmployeeCanBeValidator(
        Builder|Relation|FundRequest $query,
        Employee $employee,
    ): Builder|Relation|FundRequest {
        return $query->where(fn (Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee, [
            Permission::VALIDATE_RECORDS,
        ]));
    }

    /**
     * @param Builder|Relation|FundRequest $query
     * @param Employee $employee
     * @return Builder|Relation|FundRequest
     */
    public static function whereEmployeeIsValidatorsSupervisor(
        Builder|Relation|FundRequest $query,
        Employee $employee,
    ): Builder|Relation|FundRequest {
        return $query->where(fn (Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee, [
            Permission::MANAGE_VALIDATORS,
        ]));
    }

    /**
     * @param Builder|Relation|FundRequest $query
     * @param Employee $employee
     * @param array|string $permission
     * @return Builder|Relation|FundRequest
     */
    public static function whereSponsorEmployeeHasPermission(
        Builder|Relation|FundRequest $query,
        Employee $employee,
        array|string $permission,
    ): Builder|Relation|FundRequest {
        $funds = Fund::query()
            ->whereHas('organization', function (Builder $q) use ($employee, $permission) {
                $q->where('id', $employee->organization_id);
                OrganizationQuery::whereHasPermissions($q, $employee->identity_address, $permission);
            })
            ->select('id');

        return $query->whereIn('fund_id', $funds);
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @return Builder|Relation|FundRequest
     */
    public static function whereGroupStatePending(
        Builder|Relation|FundRequest $builder,
    ): Builder|Relation|FundRequest {
        return $builder->where('state', FundRequest::STATE_PENDING)->whereNull('employee_id');
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @return Builder|Relation|FundRequest
     */
    public static function whereGroupStateAssigned(
        Builder|Relation|FundRequest $builder,
    ): Builder|Relation|FundRequest {
        return $builder->where('state', FundRequest::STATE_PENDING)->whereNotNull('employee_id');
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @return Builder|Relation|FundRequest
     */
    public static function whereGroupStateResolved(
        Builder|Relation|FundRequest $builder,
    ): Builder|Relation|FundRequest {
        return $builder->whereIn('fund_requests.state', FundRequest::STATES_RESOLVED);
    }

    /**
     * @param Builder|Relation|FundRequest $builder
     * @param string|null $stateGroup
     * @return Builder|Relation|FundRequest
     */
    public static function whereGroupState(
        Builder|Relation|FundRequest $builder,
        ?string $stateGroup = null,
    ): Builder|Relation|FundRequest {
        return match ($stateGroup) {
            'pending' => self::whereGroupStatePending($builder),
            'assigned' => self::whereGroupStateAssigned($builder),
            'resolved' => self::whereGroupStateResolved($builder),
            default => $builder,
        };
    }
}
