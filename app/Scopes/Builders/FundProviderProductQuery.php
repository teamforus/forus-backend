<?php


namespace App\Scopes\Builders;

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
     * @param Builder $builder
     * @param $identity_address
     * @return Builder
     */
    public static function whereInLimitsFilter(Builder $builder, $identity_address): Builder {
        $limit_total = \DB::raw('`fund_provider_products`.`limit_total`');
        $limit_per_identity = \DB::raw('`fund_provider_products`.`limit_per_identity`');

        $builder->whereHas('voucher_transactions', null, '<', $limit_total);

        return $builder->whereHas('voucher_transactions', static function(
            Builder $builder
        ) use ($identity_address) {
            $builder->whereHas('voucher', static function(
                Builder $builder
            ) use ($identity_address) {
                $builder->whereIn('vouchers.identity_address', (array) $identity_address);
            });
        }, '<', $limit_per_identity);
    }
}