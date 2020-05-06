<?php


namespace App\Scopes\Builders;

use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;

class OrganizationQuery
{
    /**
     * @param Builder $builder
     * @param string $identityAddress
     * @param $permissions
     * @return Organization|Builder|mixed
     */
    public static function whereHasPermissions(
        Builder $builder,
        string $identityAddress,
        $permissions
    ) {
        return $builder->where(function(
            Builder $builder
        ) use ($identityAddress, $permissions) {
            $builder->whereHas('employees', function(
                Builder $builder
            ) use ($identityAddress, $permissions) {
                $builder->where('employees.identity_address', $identityAddress);

                $builder->whereHas('roles.permissions', function(
                    Builder $builder
                ) use ($permissions) {
                    $builder->whereIn('permissions.key', (array) $permissions);
                });
            })->orWhere('organizations.identity_address', $identityAddress);
        });
    }

    /**
     * @param Builder $query
     * @param string $identity_address
     * @param Voucher $voucher
     * @return Organization|Builder
     */
    public static function whereHasPermissionToScanVoucher(
        Builder $query,
        string $identity_address,
        Voucher $voucher
    ) {
        return self::whereHasPermissions(
            $query, $identity_address,'scan_vouchers'
        )->whereHas('fund_providers', function(
            Builder $builder
        ) use ($voucher) {
            if ($voucher->type == Voucher::TYPE_PRODUCT) {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder, $voucher->fund_id, 'product', $voucher->product_id
                );
            } else {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder, $voucher->fund_id, 'budget'
                );
            }
        });
    }
}