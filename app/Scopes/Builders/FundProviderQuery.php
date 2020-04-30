<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

class FundProviderQuery
{
    /**
     * @param Builder $query
     * @param $fund_id
     * @param null $type
     * @param null $product_id
     * @return Builder
     */
    public static function whereApprovedForFundsFilter(
        Builder $query,
        $fund_id,
        $type = null,
        $product_id = null
    ) {
        return $query->where(function(Builder $builder) use ($fund_id, $type, $product_id) {
            $builder->whereIn('fund_id', (array) $fund_id);

            $builder->where(function(Builder $builder) use ($type, $product_id) {
                if ($type == null) {
                    $builder->where('allow_budget', true);
                    $builder->orWhere('allow_products', true);

                    if ($product_id) {
                        $builder->orWhereHas('fund_provider_products', function(
                            Builder $builder
                        ) use ($product_id) {
                            $builder->whereIn(
                                'fund_provider_products.product_id',
                                (array) $product_id
                            );
                        });
                    } else {
                        $builder->orWhereHas('fund_provider_products');
                    }
                } else if ($type == 'budget') {
                    $builder->where('allow_budget', true);
                } else if ($type == 'product') {
                    $builder->where('allow_products', true);

                    if ($product_id) {
                        $builder->orWhereHas('fund_provider_products', function(
                            Builder $builder
                        ) use ($product_id) {
                            $builder->whereIn(
                                'fund_provider_products.product_id',
                                (array) $product_id
                            );
                        });
                    } else {
                        $builder->orWhereHas('fund_provider_products');
                    }
                }
            });

            if ($type == 'product' && $product_id) {
                $builder->whereHas('organization.products', function(
                    Builder $builder
                ) use ($product_id) {
                    $builder->whereIn('products.id', (array) $product_id);
                });
            }
        });
    }

    /**
     * @param Builder $query
     * @param $fund_id
     * @return Builder
     */
    public static function wherePendingForFundsFilter(
        Builder $query,
        $fund_id
    ) {
        return $query->where(function(Builder $builder) use ($fund_id) {
            $builder->whereIn('fund_id', (array) $fund_id);

            $builder->where(function(Builder $builder) use ($fund_id) {
                $builder->where('allow_budget', false);
                $builder->where('allow_products', false);
                $builder->doesntHave('fund_provider_products');
            });
        });
    }

    /**
     * @param Builder $query
     * @param Request $request
     * @param Fund $fund
     * @return mixed
     */
    public static function sortByRevenue(
        Builder $query,
        Request $request,
        Fund $fund
    ) {
        $page = Paginator::resolveCurrentPage('page');
        $pageSize = $request->input('per_page', 15);
        $results = $query->get()->sortByDesc(function (FundProvider $fundProvider) use ($request, $fund) {
            return $fundProvider->getFinances($request, $fund)['usage'];
        });

        return new LengthAwarePaginator($results->forPage($page, $pageSize), $results->count(), $pageSize, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }
}