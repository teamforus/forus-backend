<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundRequestRecordQuery
{
    /**
     * @param Builder $query
     * @param string|array $identity_address
     * @param string|array $employee_id
     * @return Builder
     */
    public static function whereIdentityIsAssignedEmployeeFilter(
        Builder $query,
        $identity_address,
        $employee_id = null
    ): Builder {
        return $query->whereHas('employee', static function(
            Builder $builder
        ) use ($identity_address, $employee_id) {
            $builder->whereIn('employees.identity_address', (array) $identity_address);

            if (!is_null($employee_id)) {
                $builder->whereIn('employees.id', (array) $employee_id);
            }
        });
    }

    /**
     * @param Builder $query
     * @param string|array $identity_address
     * @param int|array $employee_id
     * @return Builder
     */
    public static function whereIdentityCanBeValidatorFilter(
        Builder $query,
        $identity_address,
        $employee_id = null
    ): Builder {
        return $query->where(static function(Builder $query) use ($identity_address, $employee_id) {
            // sponsor employees
            $query->whereHas('fund_request.fund.organization', static function(
                Builder $builder
            ) use ($identity_address, $employee_id) {
                OrganizationQuery::whereHasPermissions(
                    $builder, $identity_address, 'validate_records'
                );

                if ($employee_id) {
                    $builder->whereHas('employees', static function(
                        Builder $builder
                    ) use ($employee_id) {
                        $builder->whereIn('employees.id', (array) $employee_id);
                    });
                }
            });

            // external validator employees
            $query->orWhereHas('fund_criterion.fund_criterion_validators', static function(
                Builder $builder
            ) use ($identity_address, $employee_id) {
                $builder->where('accepted', 1);
                return $builder->whereHas('external_validator.validator_organization', static function(
                    Builder $builder
                ) use ($identity_address, $employee_id) {
                    OrganizationQuery::whereHasPermissions(
                        $builder, $identity_address, 'validate_records'
                    );

                    if ($employee_id) {
                        $builder->whereHas('employees', static function(Builder $builder) use ($employee_id) {
                            $builder->whereIn('employees.id', (array) $employee_id);
                        });
                    }
                });
            });
        });
    }
}
