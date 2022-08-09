<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundCriterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecordValidationQuery
{
    /**
     * @param Builder|Relation $builder
     * @param int $trustedDays
     * @return Builder
     */
    public static function whereStillTrustedQuery(
        Builder|Relation $builder,
        int $trustedDays
    ): Builder {
        return $builder->where(function(Builder $builder) use ($trustedDays) {
            $builder->where(static function(Builder $builder) use ($trustedDays) {
                $builder->whereNotNull('prevalidation_id');
                $builder->whereHas('prevalidation', static function(Builder $builder) use ($trustedDays) {
                    $builder->where('validated_at', '>=', now()->subDays($trustedDays));
                });
            });

            $builder->orWhere(static function(Builder $builder) use ($trustedDays) {
                $builder->whereNull('prevalidation_id');
                $builder->where('created_at', '>=', now()->subDays($trustedDays));
            });
        });
    }

    /**
     * @param Builder|Relation $builder
     * @param Fund $fund
     * @param FundCriterion|null $criterion
     * @return Builder
     */
    public static function whereTrustedByQuery(
        Builder|Relation $builder,
        Fund $fund,
        ?FundCriterion $criterion = null
    ): Builder {
        return $builder->where(function(Builder $builder) use ($fund, $criterion) {
            $builder->whereIn('identity_address', $fund->validatorEmployees($criterion));
            $builder->where(function(Builder $builder) use ($fund) {
                $builder->whereNull('organization_id');
                $builder->orWhere('organization_id', $fund->organization_id);
            });
        });
    }
}