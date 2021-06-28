<?php


namespace App\Scopes\Builders;

use App\Models\FundProvider;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FundProviderProductQuery
 * @package App\Scopes\Builders
 */
class FundProviderProductQuery
{
    /**
     * @param Builder|SoftDeletes $builder
     * @return Builder|SoftDeletes
     */
    public static function withTrashed(Builder $builder): Builder {
        return $builder->withTrashed();
    }

    /**
     * @param Builder $query
     * @param Voucher $voucher
     * @param array|int|null $organization_id
     * @return Builder
     */
    public static function whereAvailableForSubsidyVoucherFilter(
        Builder $query,
        Voucher $voucher,
        $organization_id = null
    ): Builder {
        $query->whereHas('product', static function(Builder $query) use ($voucher, $organization_id) {
            $query->where(static function(Builder $builder) use ($voucher, $organization_id) {
                $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(), $voucher->fund_id,'subsidy', $voucher->product_id
                );

                if ($organization_id) {
                    $providersQuery->whereIn('organization_id', (array) $organization_id);
                }

                $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
            });

            return ProductQuery::approvedForFundsAndActiveFilter($query, $voucher->fund->id);
        });

        $query->whereHas('fund_provider', static function(Builder $builder) use ($voucher) {
            $builder->where('fund_id', '=', $voucher->fund_id);
        });

        return self::whereInLimitsFilter($query, $voucher);
    }

    /**
     * @param Builder $builder
     * @param Voucher $voucher
     * @return Builder
     */
    public static function whereInLimitsFilter(
        Builder $builder,
        Voucher $voucher
    ): Builder {
        $limit_per_identity = sprintf("(`fund_provider_products`.`limit_per_identity` * %s)", $voucher->limit_multiplier);
        $limit_total = '`fund_provider_products`.`limit_total`';

        $builder->whereHas('product', static function(Builder $builder) use ($voucher, $limit_total, $limit_per_identity) {
            $builder->whereExists(function(QBuilder $builder) use ($voucher, $limit_per_identity, $limit_total) {
                $builder->selectSub(VoucherTransaction::query()
                    ->select([])
                    ->selectRaw('count(*) as `count_transactions`')
                    ->where('state', '!=', VoucherTransaction::STATE_CANCELED)
                    ->whereColumn('product_id', '=', 'products.id'),
                    'count_transactions'
                );

                $builder->selectSub(Voucher::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereDoesntHave('product_reservation')
                    ->whereDoesntHave('transactions')
                    ->whereColumn('vouchers.product_id', '=', 'products.id'),
                    'count_vouchers'
                );

                $builder->selectSub(ProductReservation::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereNotIn('state', [
                        ProductReservation::STATE_CANCELED,
                        ProductReservation::STATE_REJECTED,
                    ])
                    ->whereDoesntHave('voucher_transaction')
                    ->whereColumn('product_id', '=', 'products.id'),
                    'count_reservations'
                );

                $sumQuery = "(`count_transactions` + `count_vouchers` + `count_reservations`)";
                $builder->havingRaw("(($sumQuery < ($limit_total)) or (`limit_total_unlimited` = true))");
            });

            $builder->whereExists(function(QBuilder $builder) use ($voucher, $limit_per_identity, $limit_total) {
                $builder->selectSub(VoucherTransaction::query()
                    ->select([])
                    ->selectRaw('count(*) as `count_transactions`')
                    ->where('state', '!=', VoucherTransaction::STATE_CANCELED)
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where('voucher_id', '=', $voucher->id), 'count_transactions');

                $builder->selectSub(Voucher::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereDoesntHave('product_reservation')
                    ->whereDoesntHave('transactions')
                    ->whereColumn('vouchers.product_id', '=', 'products.id')
                    ->where('parent_id', '=', $voucher->id), 'count_vouchers');

                $builder->selectSub(ProductReservation::query()
                    ->select([])
                    ->selectRaw('count(*)')
                    ->whereNotIn('state', [
                        ProductReservation::STATE_CANCELED,
                        ProductReservation::STATE_REJECTED,
                    ])
                    ->whereDoesntHave('voucher_transaction')
                    ->whereColumn('product_id', '=', 'products.id')
                    ->where('voucher_id', '=', $voucher->id), 'count_reservations');

                $sumQuery = "(`count_transactions` + `count_vouchers` + `count_reservations`)";
                $builder->havingRaw("($sumQuery < ($limit_per_identity))");
            });
        });

        return $builder;
    }
}