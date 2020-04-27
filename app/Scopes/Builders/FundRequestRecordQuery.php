<?php


namespace App\Scopes\Builders;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class FundRequestRecordQuery
{
    /**
     * @param Builder $query
     * @param string|array $identity_address
     * @return Builder
     */
    public static function whereIdentityIsAssignedEmployeeFilter(Builder $query, $identity_address) {
        return $query->whereHas('employee', function(
            Builder $builder
        ) use ($identity_address) {
            $builder->whereIn('employees.identity_address', (array) $identity_address);
        });
    }

    /**
     * @param Builder $query
     * @param string|array $identity_address
     * @param int|array $employee_id
     * @return Builder
     */
    public static function whereIdentityCanBeValidatorFilter(Builder $query, $identity_address, $employee_id = null) {
        return $query->whereHas('fund_criterion.fund_criterion_validators', function(
            Builder $builder
        ) use ($identity_address, $employee_id) {
            return $builder->whereHas('external_validator.validator_organization', function(
                Builder $builder
            ) use ($identity_address, $employee_id) {
                OrganizationQuery::whereHasPermissions(
                    $builder,
                    $identity_address,
                    'validate_records'
                );

                if ($employee_id) {
                    $builder->whereHas('employees', function(Builder $builder) use ($employee_id) {
                        $builder->whereIn('employees.id', (array) $employee_id);
                    });
                }
            });
        });
    }
}
