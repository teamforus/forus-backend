<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundQuery
{
    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereActiveFilter(
        Builder|Relation|Fund $query
    ): Builder|Relation|Fund {
        return $query->where([
            'state' => Fund::STATE_ACTIVE
        ])->whereDate('end_date', '>=', today());
    }

    /**
     * @param Builder $query
     * @param bool $includeArchived
     * @param bool $includeExternal
     * @return Builder
     */
    public static function whereActiveOrClosedFilter(
        Builder $query,
        bool $includeArchived = true,
        bool $includeExternal = true
    ): Builder {
        return $query->whereIn('state', [
            Fund::STATE_ACTIVE, Fund::STATE_CLOSED
        ])->where(function(Builder $builder) use ($includeArchived, $includeExternal) {
            if (!$includeArchived) {
                $builder->where('archived', false);
            }

            if (!$includeExternal) {
                $builder->where('type', '!=', Fund::TYPE_EXTERNAL);
            }
        });
    }

    /**
     * @param Builder $query
     * @param Product $product
     * @param bool $providerNotExcluded
     * @return Builder
     * @noinspection PhpUnused
     */
    public static function whereProductsAreApprovedFilter(
        Builder $query,
        Product $product,
        bool $providerNotExcluded = false
    ): Builder {
        if ($product->sponsor_organization_id) {
            $query->where('organization_id', $product->sponsor_organization_id);
        }

        $query->whereDoesntHave('providers.product_exclusions', static function(Builder $builder) use ($product) {
            $builder->where('product_id', $product->id);
        });

        return $query->where(function(Builder $builder) use ($product, $providerNotExcluded) {
            $builder->where(function(Builder $builder) use ($product, $providerNotExcluded) {
                $builder->where('type', '=', Fund::TYPE_BUDGET);

                $builder->whereHas('providers', static function(
                    Builder $builder
                ) use ($product, $providerNotExcluded) {
                    $builder->where('state', FundProvider::STATE_ACCEPTED);
                    $builder->where('allow_products', '=', true);

                    if ($providerNotExcluded) {
                        $builder->where('excluded', false);
                    }

                    $builder->whereHas('organization.products', static function(Builder $builder) use ($product) {
                        $builder->where('products.id', $product->id);
                    });
                });
            });

            $builder->orWhereHas('providers', static function(
                Builder $builder
            ) use ($product, $providerNotExcluded) {
                $builder->where('state', FundProvider::STATE_ACCEPTED);

                if ($providerNotExcluded) {
                    $builder->where('excluded', false);
                }

                $builder->whereHas('fund_provider_products', static function(Builder $builder) use ($product) {
                    $builder->where('product_id', $product->id);
                });
            });
        });
    }

    /**
     * @param Builder $query
     * @param Product $product
     * @return Builder
     */
    public static function whereProductsAreApprovedAndActiveFilter(
        Builder $query,
        Product $product
    ): Builder {
        return self::whereProductsAreApprovedFilter(self::whereActiveFilter($query), $product, true);
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
     * @param array|int $implementation_id
     * @return Builder
     */
    public static function whereImplementationIdFilter(Builder $query, $implementation_id): Builder
    {
        return $query->whereHas('fund_config', static function(Builder $builder) use ($implementation_id) {
            $builder->whereIn('implementation_id', (array) $implementation_id);
        });
    }

    /**
     * @param Builder $query
     * @param $organization_id
     * @return Builder
     */
    public static function whereHasProviderFilter(Builder $query, $organization_id): Builder
    {
        return $query->whereHas('providers.organization', static function(
            Builder $builder
        ) use ($organization_id) {
            $builder->whereIn('organizations.id', (array) $organization_id);
        });
    }

    /**
     * @param Builder|Relation $query
     * @param string $q
     * @return Builder|Relation
     */
    public static function whereQueryFilter(Builder|Relation $query, string $q): Builder|Relation
    {
        return $query->where(function(Builder $builder) use ($q) {
            $builder->where('name', 'LIKE', "%$q%");
            $builder->orWhere('description_text', 'LIKE', "%$q%");
            $builder->orWhere('description_short', 'LIKE', "%$q%");
            $builder->orWhereRelation('organization', 'organizations.name', 'LIKE', "%$q%");
        });
    }

    /**
     * @param Builder $query
     * @param array $states
     * @return Builder
     */
    public static function sortByState(Builder $query, array $states): Builder
    {
        foreach ($states as $state) {
            $query->orderByRaw('`funds`.`state` = ? DESC', [$state]);
        }

        return $query;
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsConfiguredByForus(
        Builder|Relation|Fund $query
    ): Builder|Relation|Fund {
        return $query->whereHas('fund_config', static function (Builder $query) {
            return $query->where('is_configured', true);
        });
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternal(Builder|Relation|Fund $query): Builder|Relation|Fund
    {
        return $query->where('type', '!=', Fund::TYPE_EXTERNAL);
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereExpired(Builder|Relation|Fund $query): Builder|Relation|Fund
    {
        return $query->where('end_date', '<', now()->format('Y-m-d'));
    }
  
    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternalConfiguredAndActive(
        Builder|Relation|Fund $query
    ): Builder|Relation|Fund {
        return self::whereIsInternal(self::whereActiveFilter(self::whereIsConfiguredByForus($query)));
    }

    /**
     * @param Builder|Relation|Fund $query
     * @return Builder|Relation|Fund
     */
    public static function whereIsInternalAndConfigured(
        Builder|Relation|Fund $query
    ): Builder|Relation|Fund {
        return self::whereIsInternal(self::whereIsConfiguredByForus($query));
    }

    /**
     * @param Builder|Relation|Fund $query
     * @param string $balanceProvider
     * @return Builder|Relation|Fund
     */
    public static function whereTopUpAndBalanceUpdateAvailable(
        Builder|Relation|Fund $query,
        string $balanceProvider
    ): Builder|Relation|Fund{
        return $query->whereHas('organization', function(Builder $builder) {
            $builder->whereHas('bank_connection_active');
        })->where(function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->where(function(Builder $builder) use ($balanceProvider) {
            if ($balanceProvider === Fund::BALANCE_PROVIDER_TOP_UPS) {
                $builder->whereHas('top_ups');
            }

            $builder->where('balance_provider', $balanceProvider);
        });
    }
}