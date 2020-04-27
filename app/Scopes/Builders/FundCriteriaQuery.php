<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundCriteriaQuery
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
        return $query->whereHas('fund_criterion_validators', function (
            Builder $builder
        ) use ($organization_id) {
            $builder->whereHas('external_validator.validator_organization', function(
                Builder $builder
            ) use ($organization_id) {
                $builder->whereIn('organizations.id', (array) $organization_id);
            });
        });
    }
}