<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class ImplementationQuery
{
    /**
     * @param Builder $query
     * @param $organization_id
     * @return Builder
     */
    public static function whereOrganizationIdFilter(Builder $query, $organization_id) {
        return $query->whereHas('fund_configs.fund', function(
            Builder $builder
        ) use ($organization_id) {
            $builder->whereIn('organization_id', (array) $organization_id);
        });
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $query, string $q) {
        return $query->where('name', 'LIKE', "%{$q}%");
    }
}