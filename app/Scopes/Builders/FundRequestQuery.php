<?php


namespace App\Scopes\Builders;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class FundRequestQuery
{
    /**
     * @param Builder $query
     * @param int|array $organization_id External validator organization id
     * @return Builder
     */
    public static function whereExternalValidatorFilter(Builder $query, $organization_id) {
        return $query->whereHas('employee', function(
            \Illuminate\Database\Query\Builder $builder
        ) use ($organization_id) {
            $builder->whereIn('employees.organization_id', (array) $organization_id);
        });
    }

    /**
     * TODO: TEST
     * @param Builder $query
     * @param string|array $identity_address
     * @return Builder
     */
    public static function whereIdentityCanBeValidatorFilter(Builder $query, $identity_address) {
        return $query->whereHas('fund.organization', function(Builder $builder) use ($identity_address) {
            OrganizationQuery::whereHasPermissions($builder, $identity_address, 'validate_records');
        })->orWhereHas('fund', function(Builder $builder) use ($identity_address) {
            FundQuery::whereExternalValidatorFilter(
                $builder,
                OrganizationQuery::whereHasPermissions(
                    Organization::query(),
                    $identity_address,
                    'validate_records'
                )->pluck('organizations.id')->toArray()
            );
        });
    }
}
