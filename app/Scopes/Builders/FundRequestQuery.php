<?php


namespace App\Scopes\Builders;

use App\Models\FundRequest;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class FundRequestQuery
{
    /**
     * @param Builder|Relation $builder
     * @param string $identity_address
     * @return Builder|Relation
     */
    public static function whereApprovedAndVoucherIsActive($builder, string $identity_address)
    {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->whereHas('fund', static function(Builder $builder) use ($identity_address) {
                $builder->whereHas('vouchers', static function(Builder $builder) use ($identity_address) {
                    $builder->where('identity_address', $identity_address);
                    VoucherQuery::whereNotExpired($builder);
                });
            })->where([
                'state' => FundRequest::STATE_APPROVED,
                'identity_address' => $identity_address,
            ]);
        });
    }

    /**
     * @param Builder|Relation $builder
     * @param string $identity_address
     * @return Builder|Relation
     */
    public static function whereIsPending($builder, string $identity_address)
    {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->where([
                'state' => FundRequest::STATE_PENDING,
                'identity_address' => $identity_address,
            ]);
        });
    }
    /**
     * @param Builder|Relation $builder
     * @param string $identity_address
     * @return Builder|Relation
     */
    public static function wherePendingOrApprovedAndVoucherIsActive($builder, string $identity_address)
    {
        return $builder->where(function(Builder $builder) use ($identity_address) {
            $builder->where(function(Builder $builder) use ($identity_address) {
                static::whereApprovedAndVoucherIsActive($builder, $identity_address);
            });

            $builder->orWhere(function(Builder $builder) use ($identity_address) {
                static::whereIsPending($builder, $identity_address);
            });
        });
    }

    /**
     * @param Builder $builder
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $builder, string $q): Builder
    {
        return $builder->where(function (Builder $query) use ($q) {
            $query->whereHas('fund', static function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });

            $query->orWhereHas('identity.primary_email', static function(Builder $builder) use ($q) {
                $builder->where('email', 'LIKE', "%$q%");
            });

            if ($bsnIdentity = Identity::findByBsn($q)) {
                $query->orWhere('identity_address', '=', $bsnIdentity->address);
            }

            $query->orWhere('id', '=', $q);
        });
    }
}
