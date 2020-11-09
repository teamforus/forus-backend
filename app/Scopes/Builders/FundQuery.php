<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use Illuminate\Database\Eloquent\Builder;

class FundQuery
{
    /**
     * @param Builder $query
     * @return Builder
     */
    public static function whereActiveFilter(Builder $query): Builder {
        return $query->where([
            'state' => Fund::STATE_ACTIVE
        ])->where('end_date', '>', now());
    }

    /**
     * @param Builder $query
     * @param $product_id
     * @return Builder
     */
    public static function whereProductsAreApprovedFilter(Builder $query, $product_id): Builder {
        return $query->where(function(Builder $builder) use ($product_id) {
            $builder->where(function(Builder $builder) use ($product_id) {
                $builder->where('type', '=', Fund::TYPE_BUDGET);

                $builder->whereHas('providers', static function(Builder $builder) use ($product_id) {
                    $builder->whereHas('organization.products', static function(Builder $builder) use ($product_id) {
                        $builder->whereIn('products.id', (array) $product_id);
                    })->where('allow_products', '=', true);
                });
            });

            $builder->orWhereHas('providers', static function(Builder $builder) use ($product_id) {
                $builder->whereDoesntHave('product_exclusions', static function(Builder $builder) use ($product_id) {
                    $builder->whereIn('product_id', (array) $product_id);
                });

                $builder->whereHas('fund_provider_products', static function(Builder $builder) use ($product_id) {
                    $builder->whereIn('product_id', (array) $product_id);
                });
            });
        });
    }

    /**
     * @param Builder $query
     * @param $product_id
     * @return Builder
     */
    public static function whereProductsAreApprovedAndActiveFilter(
        Builder $query,
        $product_id
    ): Builder {
        return self::whereProductsAreApprovedFilter(self::whereActiveFilter($query), $product_id);
    }

    /**
     * @param Builder $query
     * @param int|array $organization_id External validator organization id
     * @param bool|null $accepted
     * @return Builder
     */
    public static function whereExternalValidatorFilter(
        Builder $query,
        $organization_id,
        ?bool $accepted = null
    ): Builder {
        return $query->whereHas('criteria.fund_criterion_validators', static function(
            Builder $builder
        ) use ($organization_id, $accepted) {
            if (!is_null($accepted)) {
                $builder->where(compact('accepted'));
            }

            $builder->whereHas('external_validator.validator_organization', static function(
                Builder $builder
            ) use ($organization_id) {
                $builder->whereIn('organizations.id', (array) $organization_id);
            });
        });
    }

    /**
     * @param Builder $query
     * @param $implementation_id
     * @return Builder
     */
    public static function whereImplementationIdFilter(
        Builder $query,
        $implementation_id
    ): Builder {
        return $query->whereHas('fund_config', static function(
            Builder $builder
        ) use ($implementation_id) {
            $builder->whereIn('implementation_id', (array) $implementation_id);
        });
    }

    /**
     * @param Builder $query
     * @param $organization_id
     * @return Builder
     */
    public static function whereHasProviderFilter(
        Builder $query,
        $organization_id
    ): Builder {
        return $query->whereHas('providers.organization', static function(
            Builder $builder
        ) use ($organization_id) {
            $builder->whereIn('organizations.id', (array) $organization_id);
        });
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $query, string $q): Builder {
        return $query->where('name', 'LIKE', "%{$q}%");
    }

    /**
     * @param Builder $query
     * @param array $states
     * @return Builder
     */
    public static function sortByState(Builder $query, array $states): Builder {
        foreach ($states as $state) {
            $query->orderByRaw('`funds`.`state` = ? DESC', [$state]);
        }

        return $query;
    }
}