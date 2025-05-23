<?php

namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\RecordValidation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecordValidationQuery
{
    /**
     * @param Builder|Relation|RecordValidation $builder
     * @param int $trustedDays
     * @param Carbon|null $startDate
     * @return Builder|Relation|RecordValidation
     */
    public static function whereStillTrustedQuery(
        Builder|Relation|RecordValidation $builder,
        int $trustedDays,
        ?Carbon $startDate,
    ): Builder|Relation|RecordValidation {
        return $builder->where(function (Builder $builder) use ($trustedDays, $startDate) {
            $builder->where(static function (Builder $builder) use ($trustedDays, $startDate) {
                $builder->whereNotNull('prevalidation_id');
                $builder->whereHas('prevalidation', static function (Builder $builder) use ($trustedDays, $startDate) {
                    $builder->where('validated_at', '>=', now()->subDays($trustedDays));

                    if ($startDate) {
                        $builder->where('validated_at', '>=', $startDate);
                    }
                });
            });

            $builder->orWhere(static function (Builder $builder) use ($trustedDays, $startDate) {
                $builder->whereNull('prevalidation_id');
                $builder->where('created_at', '>=', now()->subDays($trustedDays));

                if ($startDate) {
                    $builder->where('created_at', '>=', $startDate->format('Y-m-d'));
                }
            });
        });
    }

    /**
     * @param Builder|Relation|RecordValidation $builder
     * @param Fund $fund
     * @return Builder|Relation|RecordValidation
     */
    public static function whereTrustedByQuery(
        Builder|Relation|RecordValidation $builder,
        Fund $fund,
    ): Builder|Relation|RecordValidation {
        return $builder->where(function (Builder $builder) use ($fund) {
            $builder->where(function (Builder $builder) use ($fund) {
                $builder->whereNull('prevalidation_id');
                $builder->whereIn('identity_address', $fund->validatorEmployees());

                $builder->where(function (Builder $builder) use ($fund) {
                    $builder->whereNull('organization_id');
                    $builder->orWhere('organization_id', $fund->organization_id);
                });
            });

            $builder->orWhereHas('prevalidation', function (Builder $builder) use ($fund) {
                $builder->where('organization_id', $fund->organization_id);
            });
        });
    }
}
