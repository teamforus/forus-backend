<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class FundCriteriaQuery
{
    /**
     * @param Builder $query
     * @param int|array $organization_id External validator organization id
     * @param bool|null $accepted
     * @return Builder
     */
    public static function whereHasExternalValidatorFilter(
        Builder $query,
        $organization_id,
        ?bool $accepted = null
    ): Builder {
        return $query->whereHas('fund_criterion_validators', static function(
            Builder $builder
        ) use ($organization_id, $accepted) {
            if (!is_null($accepted)) {
                $builder->where('accepted', $accepted);
            }

            $builder->whereHas('external_validator.validator_organization', static function(
                Builder $builder
            ) use ($organization_id) {
                $builder->whereIn('organizations.id', (array) $organization_id);
            });
        });
    }
}