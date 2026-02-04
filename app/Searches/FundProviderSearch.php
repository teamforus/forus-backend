<?php

namespace App\Searches;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|FundProvider $builder
     * @param Organization $organization
     */
    public function __construct(
        array $filters,
        Builder|Relation|FundProvider $builder,
        protected Organization $organization,
    ) {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|FundProvider
     */
    public function query(): Builder|Relation|FundProvider
    {
        /** @var Builder|Relation|FundProvider $builder */
        $builder = parent::query();

        $allow_products = $this->getFilter('allow_products');
        $has_products = $this->getFilter('has_products');

        $fundsQuery = $this->organization->funds()->where('archived', false);
        $builder->whereIn('fund_id', $fundsQuery->select('id')->getQuery());

        if ($q = $this->getFilter('q')) {
            $builder->where(static function (Builder $builder) use ($q) {
                $builder->whereHas('organization', function (Builder $query) use ($q) {
                    $query->where('name', 'like', "%$q%");
                    $query->orWhere('kvk', 'like', "%$q%");
                    $query->orWhere('email', 'like', "%$q%");
                    $query->orWhere('phone', 'like', "%$q%");
                });

                $builder->orWhereHas('fund', function (Builder $query) use ($q) {
                    $query->where('name', 'like', "%$q%");
                });
            });
        }

        if ($this->hasFilter('fund_id')) {
            $builder->where('fund_id', $this->getFilter('fund_id'));
        }

        if ($this->hasFilter('fund_ids')) {
            $builder->whereIn('fund_id', $this->getFilter('fund_ids'));
        }

        if ($this->hasFilter('implementation_id') && $implementation_id = $this->getFilter('implementation_id')) {
            $builder->whereRelation('fund.fund_config', 'implementation_id', $implementation_id);
        }

        if ($this->hasFilter('state')) {
            $builder->where('state', $this->getFilter('state'));
        }

        if ($this->hasFilter('organization_id')) {
            $builder->where('organization_id', $this->getFilter('organization_id'));
        }

        if ($this->getFilter('allow_budget') !== null) {
            $builder
                ->whereHas('fund', fn (Builder $builder) => FundQuery::whereIsInternal($builder))
                ->where('allow_budget', (bool) $this->getFilter('allow_budget'));
        }

        if ($has_products) {
            $builder->whereHas('organization.products', function (Builder $builder) use ($fundsQuery) {
                ProductQuery::whereNotExpired($builder);
                ProductQuery::whereFundNotExcluded($builder, $fundsQuery->pluck('id')->toArray());
            });
        }

        if (!$has_products && $has_products !== null) {
            $builder->whereDoesntHave('organization.products', function (Builder $builder) use ($fundsQuery) {
                ProductQuery::whereNotExpired($builder);
                ProductQuery::whereFundNotExcluded($builder, $fundsQuery->pluck('id')->toArray());
            });
        }

        if ($allow_products !== null) {
            if ($allow_products === 'some') {
                $builder->where(function (Builder $builder) {
                    $builder->whereHas('fund', function (Builder $builder) {
                        FundQuery::whereIsInternal($builder)->whereHas('products');
                    });

                    $builder->orWhereHas('fund_provider_products.product');
                });
            } else {
                $builder->where(function (Builder $builder) use ($allow_products) {
                    $builder->whereHas('fund', function (Builder $builder) use ($allow_products) {
                        FundQuery::whereIsInternal($builder)->where('allow_products', (bool) $allow_products);
                    });

                    $builder->orWhere(function (Builder $builder) use ($allow_products) {
                        if ($allow_products) {
                            $builder->whereHas('fund_provider_products.product');
                        } else {
                            $builder->whereDoesntHave('fund_provider_products.product');
                        }
                    });
                });
            }
        }

        if ($this->getFilter('allow_extra_payments') !== null) {
            $builder->where('allow_extra_payments', (bool) $this->getFilter('allow_extra_payments'));
        }

        return $builder;
    }
}
