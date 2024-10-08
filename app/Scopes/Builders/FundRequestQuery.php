<?php


namespace App\Scopes\Builders;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestQuery
{
    /**
     * @param Builder|Relation $builder
     * @param string $identity_address
     * @return Builder|Relation
     */
    public static function whereApprovedAndVoucherIsActive(
        Builder|Relation $builder,
        string $identity_address,
    ): Builder|Relation {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->whereHas('fund', static function(Builder $builder) use ($identity_address) {
                $builder->whereHas('vouchers', static function(Builder $builder) use ($identity_address) {
                    $builder->where('identity_address', $identity_address);
                    VoucherQuery::whereNotExpired($builder);
                });
            })->where([
                'state' => FundRequest::STATE_APPROVED,
                'identity_address' => $identity_address,
            ]);
        });
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereNoEmployee(
        Builder|Relation $builder,
    ): Builder|Relation {
        return $builder->whereNull('employee_id');
    }

    /**
     * @param Builder|Relation $builder
     * @param string|null $identity_address
     * @return Builder|Relation
     */
    public static function whereIsPending(
        Builder|Relation $builder,
        string $identity_address = null,
    ): Builder|Relation {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->where([
                'fund_requests.state' => FundRequest::STATE_PENDING,
            ]);

            if ($identity_address) {
                $builder->where([
                    'identity_address' => $identity_address,
                ]);
            }
        });
    }

    /**
     * @param Builder|Relation $builder
     * @return Builder|Relation
     */
    public static function whereIsResolved(
        Builder|Relation $builder,
    ): Builder|Relation {
        return $builder->whereIn('fund_requests.state', [
            FundRequest::STATE_APPROVED,
            FundRequest::STATE_APPROVED_PARTLY,
            FundRequest::STATE_DECLINED,
            FundRequest::STATE_DISREGARDED
        ]);
    }

    /**
     * @param Builder|Relation $builder
     * @param string $identity_address
     * @return Builder|Relation
     */
    public static function wherePendingOrApprovedAndVoucherIsActive(
        Builder|Relation $builder,
        string $identity_address,
    ): Builder|Relation {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->where(function(Builder $builder) use ($identity_address) {
                static::whereApprovedAndVoucherIsActive($builder, $identity_address);
            });

            $builder->orWhere(function(Builder $builder) use ($identity_address) {
                static::whereIsPending($builder, $identity_address);
            });
        });
    }

    /**
     * @param Builder|Relation $builder
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(
        Builder|Relation $builder,
        string $q,
    ): Builder|Relation {
        return $builder->where(function (Builder $query) use ($q) {
            $query->whereHas('fund', static function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });

            $query->orWhereHas('identity.primary_email', static function(Builder $builder) use ($q) {
                $builder->where('email', 'LIKE', "%$q%");
            });

            if ($bsnIdentity = Identity::findByBsn($q, false)) {
                $query->orWhere('identity_address', '=', $bsnIdentity->address);
            }

            $query->orWhere('id', '=', $q);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeIsValidatorOrSupervisor(
        Relation|Builder $query,
        Employee $employee,
    ): Relation|Builder {
        return $query->where(static function(Builder $builder) use ($employee) {
            // validator
            $builder->where(fn(Builder $q) => static::whereEmployeeCanBeValidator($q, $employee));

            // supervisor
            $builder->orWhere(fn(Builder $q) => static::whereEmployeeIsValidatorsSupervisor($q, $employee));
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeCanBeValidator(
        Relation|Builder $query,
        Employee $employee
    ): Relation|Builder {
        return $query->where(fn(Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee, [
            Permission::VALIDATE_RECORDS,
        ]));
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeIsValidatorsSupervisor(
        Relation|Builder $query,
        Employee $employee
    ): Relation|Builder {
        return $query->where(fn(Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee, [
            Permission::MANAGE_VALIDATORS,
        ]));
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @param array|string $permission
     * @return Builder|Relation
     */
    public static function whereSponsorEmployeeHasPermission(
        Relation|Builder $query,
        Employee $employee,
        array|string $permission,
    ): Relation|Builder {
        $funds = Fund::query()
            ->whereHas('organization', function (Builder $q) use ($employee, $permission) {
                $q->where('id', $employee->organization_id);
                OrganizationQuery::whereHasPermissions($q, $employee->identity_address, $permission);
            })
            ->select('id');

        return $query->whereIn('fund_id', $funds);
    }
}
