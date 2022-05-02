<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmployeeQuery
{
    /**
     * @param Builder|Relation $query
     * @param string|array $permissions
     * @return Builder|Relation
     */
    public static function whereHasPermissionFilter($query, $permissions)
    {
        return $query->whereHas('roles.permissions', function(Builder $builder) use ($permissions) {
            $builder->whereIn('permissions.key', (array) $permissions);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param string|array $roles
     * @return Builder|Relation
     */
    public static function whereHasRoleFilter($query, $roles)
    {
        return $query->whereHas('roles', function(Builder $builder) use ($roles) {
            $builder->whereIn('roles.key', (array) $roles);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param Builder|Relation|array $records
     * @return Builder|Relation
     */
    public static function whereCanValidateRecords($query, $records)
    {
        return $query->where(function($builder) use ($records) {
            static::whereHasPermissionFilter($builder, 'validate_records');

            $builder->whereHas('organization', function(Builder $builder) use ($records) {
                // internal funds
                $builder->whereHas('funds.fund_requests.records', fn(Builder $q) => $q->whereIn('fund_request_records.id', $records));

                // external funds
                $builder->orWhereHas('validated_organizations.organization.funds.criteria', function(Builder $builder) use ($records) {
                    $builder->whereHas('fund_request_record', fn(Builder $q) => $q->whereIn('fund_request_records.id', $records));

                    $builder->whereHas('fund_criterion_validators', fn(Builder $q) => $q->where([
                        'accepted' => true,
                    ])->whereColumn([
                        'organization_validator_id' => 'organization_validators.id',
                    ]));
                });
            });
        });
    }
}