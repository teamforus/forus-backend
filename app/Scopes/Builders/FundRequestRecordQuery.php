<?php


namespace App\Scopes\Builders;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestRecordQuery
{
    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeIsAssignedValidator($query, Employee $employee)
    {
        return $query->whereHas('employee', static function(Builder $builder) use ($employee) {
            $builder->where('employees.identity_address', $employee->identity_address);
            $builder->where('employees.id', $employee->id);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeIsValidatorOrSupervisor($query, Employee $employee)
    {
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
    public static function whereEmployeeCanBeValidator($query, Employee $employee)
    {
        return $query->where(static function(Builder $builder) use ($employee) {
            // sponsor employees
            $builder->where(fn(Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee));

            // sponsor employees
            $builder->orWhere(fn(Builder $q) => static::whereValidatorEmployeeHasPermission($q, $employee));
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @param string|array $permission
     * @return Builder|Relation
     */
    protected static function whereSponsorEmployeeHasPermission($query, Employee $employee, $permission = 'validate_records')
    {
        return $query->whereHas('fund_request.fund.organization', fn(Builder $q) => OrganizationQuery::whereHasPermissions(
            $q->whereHas('employees', fn(Builder $q) => $q->where('employees.id', $employee->id)),
            $employee->identity_address,
            $permission
        ));
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @param string|array $permission
     * @return Builder|Relation
     */
    protected static function whereValidatorEmployeeHasPermission($query, Employee $employee, $permission = 'validate_records')
    {
        return $query->orWhereHas('fund_criterion.fund_criterion_validators', function(Builder $builder) use ($employee, $permission) {
            $builder->whereHas('external_validator.validator_organization', fn(Builder $q) => OrganizationQuery::whereHasPermissions(
                $q->whereHas('employees', fn(Builder $q) => $q->where('employees.id', $employee->id)),
                $employee->identity_address,
                $permission
            ))->where('accepted', 1);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Employee $employee
     * @return Builder|Relation
     */
    public static function whereEmployeeIsValidatorsSupervisor($query, Employee $employee)
    {
        return $query->where(static function(Builder $builder) use ($employee) {
            // sponsor employees
            $builder->where(fn(Builder $q) => static::whereSponsorEmployeeHasPermission($q, $employee, 'manage_validators'));

            // sponsor employees
            $builder->orWhere(fn(Builder $q) => static::whereValidatorEmployeeHasPermission($q, $employee, 'manage_validators'));
        });
    }
}
