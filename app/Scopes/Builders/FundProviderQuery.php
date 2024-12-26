<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundProviderQuery extends BaseQuery
{
    /**
     * @param Builder|Relation|FundProvider $query
     * @param $fund_id
     * @param null $type
     * @param null $product_id
     * @return Builder|Relation|FundProvider
     */
    public static function whereApprovedForFundsFilter(
        Builder|Relation|FundProvider $query,
        $fund_id,
        $type = null,
        $product_id = null,
    ): Builder|Relation|FundProvider {
        return $query->where(static function(Builder $builder) use ($fund_id, $type, $product_id) {
            $builder->where('state', FundProvider::STATE_ACCEPTED);
            $builder->where('excluded', false);
            $builder->whereIn('fund_id', self::isQueryable($fund_id) ? $fund_id : (array) $fund_id);

            $builder->where(static function(Builder $builder) use ($type, $product_id) {
                if ($type === null) {
                    $builder->where(function(Builder $builder) {
                        $builder->where('allow_budget', true);
                        $builder->orWhere('allow_products', true);
                    });
                } else if ($type === 'budget') {
                    $builder->where('allow_budget', true);
                } else if ($type === 'product') {
                    $builder->where('allow_products', true);
                }

                if ($type === null || $type === 'product' || $type === 'subsidy') {
                    if ($product_id) {
                        $builder->orWhereHas('fund_provider_products', static function(Builder $builder) use ($product_id) {
                            $builder->whereHas('product', static function(Builder $builder) use ($product_id) {
                                $builder->whereIn('products.id', (array) $product_id);
                            });
                        });
                    } else {
                        $builder->orWhereHas('fund_provider_products.product');
                    }
                }
            });

            if ($type === 'product' && $product_id) {
                $builder->whereHas('organization.products', static function(Builder $builder) use ($product_id) {
                    $builder->whereIn('products.id', (array) $product_id);
                });
            }
        });
    }

    /**
     * @param Builder|Relation|FundProvider $query
     * @param int|array $fund_id
     * @return Builder|Relation|FundProvider
     */
    public static function whereHasTransactions(
        Builder|Relation|FundProvider $query,
        int|array $fund_id,
    ): Builder|Relation|FundProvider {
        return $query->whereHas('organization', function(Builder $builder) use ($fund_id) {
            $builder->whereHas('voucher_transactions.voucher', function(Builder $builder) use ($fund_id) {
                return $builder->whereIn('fund_id', (array) $fund_id);
            });
        });
    }

    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function queryFilter(
        Builder $query,
        string $q = ''
    ): Builder {
        return $query->whereHas('organization', function(Builder $builder) use ($q) {
            return $builder->where('name', 'LIKE', "%$q%")
                ->orWhere('kvk', 'LIKE', "%$q%")
                ->orWhere('email', 'LIKE', "%$q%")
                ->orWhere('phone', 'LIKE', "%$q%");
        });
    }

    /**
     * @param Builder|Relation|FundProvider $query
     * @param string $q
     * @return Builder|Relation|FundProvider
     */
    public static function queryFilterFund(
        Builder|Relation|FundProvider $query,
        string $q = '',
    ): Builder|Relation|FundProvider {
        return $query->whereHas('fund', function(Builder $builder) use ($q) {
            $builder->where('name', 'LIKE', "%$q%");

            $builder->orWhereHas('organization', function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });
        });
    }

    /**
     * @param Builder|Relation|FundProvider $query
     * @param array|int $fund_id
     * @return Builder|Relation|FundProvider
     */
    public static function sortByRevenue(
        Builder|Relation|FundProvider $query,
        int|array $fund_id,
    ): Builder|Relation|FundProvider {
        return $query->select('fund_providers.*')->selectSub(VoucherTransaction::selectRaw(
            'sum(`voucher_transactions`.`amount`)'
        )->whereColumn(
            'voucher_transactions.organization_id',
            'fund_providers.organization_id'
        )->whereHas('voucher', function(Builder $builder) use ($fund_id) {
            $builder->whereIn('fund_id', (array) $fund_id);
        }), 'usage')->orderBy('usage', 'DESC');
    }

    /**
     * @param Builder|Relation|FundProvider $builder
     * @param array|int $fund_id
     * @return Builder|Relation|FundProvider
     */
    public static function whereDeclinedForFundsFilter(
        Builder|Relation|FundProvider $builder,
        array|int $fund_id,
    ): Builder|Relation|FundProvider {
        $providersApproved = FundProviderQuery::whereApprovedForFundsFilter(FundProvider::query(), $fund_id);

        return $builder
            ->where("fund_id", $fund_id)
            ->whereNotIn("id", $providersApproved->select("id"));
    }

    /**
     * @param Builder|Relation|FundProvider $builder
     * @return Builder|Relation|FundProvider
     */
    public static function whereApproved(
        Builder|Relation|FundProvider $builder
    ): Builder|Relation|FundProvider {
        $builder->where('state', FundProvider::STATE_ACCEPTED);
        $builder->where('excluded', false);

        $builder->where(function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhere('allow_some_products', true);
        });

        return $builder;
    }
}