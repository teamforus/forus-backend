<?php


namespace App\Scopes\Builders;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VoucherQuery
 * @package App\Scopes\Builders
 */
class VoucherQuery
{
    /**
     * @param Builder $builder
     * @param string $identity_address
     * @param $fund_id
     * @param null $organization_id Provider organization id
     * @return Builder
     */
    public static function whereProductVouchersCanBeScannedForFundBy(
        Builder $builder,
        string $identity_address,
        $fund_id,
        $organization_id = null
    ): Builder {
        return $builder->whereHas('product', static function(Builder $builder) use (
            $fund_id, $identity_address, $organization_id
        ) {
            $builder->whereHas('organization', static function(Builder $builder) use (
                $fund_id, $identity_address, $organization_id
            ) {
                if ($organization_id) {
                    $builder->whereIn('organizations.id', (array) $organization_id);
                }

                OrganizationQuery::whereHasPermissions(
                    $builder, $identity_address, 'scan_vouchers'
                );

                $builder->whereHas('fund_providers', static function(
                    Builder $builder
                ) use ($fund_id) {
                    FundProviderQuery::whereApprovedForFundsFilter(
                        $builder, $fund_id, 'product'
                    );
                });
            });

            ProductQuery::approvedForFundsFilter($builder, $fund_id);
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpired(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('vouchers.expire_at', '>=', today());

            $builder->whereHas('fund', static function(Builder $builder) {
                $builder->whereDate('end_date', '>=', today());
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereNotExpiredAndActive(Builder $builder): Builder
    {
        return self::whereNotExpired($builder)->where(
            'state', Voucher::STATE_ACTIVE
        );
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereExpired(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->where('vouchers.expire_at', '<', today());

            $builder->orWhereHas('fund', static function(Builder $builder) {
                $builder->where('end_date', '<', today());
            });
        });
    }

    /**
     * @param Builder $builder
     * @param string $query
     * @return Builder
     */
    public static function whereSearchSponsorQuery(Builder $builder, string $query): Builder
    {
        return $builder->where(static function (Builder $builder) use ($query) {
            $builder->where('note', 'LIKE', "%{$query}%");
            $builder->orWhere('activation_code', 'LIKE', "%{$query}%");
            $builder->orWhere('activation_code_uid', 'LIKE', "%{$query}%");

            if ($email_identities = identity_repo()->identityAddressesByEmailSearch($query)) {
                $builder->orWhereIn('identity_address', $email_identities);
            }

            if ($bsn_identities = record_repo()->identityAddressByBsnSearch($query)) {
                $builder->orWhereIn('identity_address', $bsn_identities);
            }

            $builder->orWhereHas('voucher_relation', function (Builder $builder) use ($query) {
                return $builder->where('bsn', 'LIKE', "%{$query}%");
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public static function whereVisibleToSponsor(Builder $builder): Builder
    {
        return $builder->where(static function(Builder $builder) {
            $builder->whereNotNull('employee_id');

            $builder->orWhere(static function(Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNull('product_id');
            });

            $builder->orWhere(static function(Builder $builder) {
                $builder->whereNull('employee_id');
                $builder->whereNotNull('product_id');
                $builder->whereNull('parent_id');
                $builder->where('returnable', false);
            });
        });
    }
}
