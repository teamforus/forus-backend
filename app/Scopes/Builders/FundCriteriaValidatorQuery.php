<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundCriteriaValidatorQuery
{
    /**
     * @param Builder $query
     * @param int|array $organization_id External validator organization id
     * @return Builder
     */
    public static function whereHasExternalValidatorFilter(
        Builder $query,
        $organization_id
    ) {
        return $query->whereHas('external_validator', function(
            Builder $builder
        ) use ($organization_id) {
            $builder->whereHas('validator_organization', function(
                Builder $builder
            ) use ($organization_id) {
                $builder->whereIn('organizations.id', (array) $organization_id);
            });
        });
    }
}