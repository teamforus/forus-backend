<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;

class OrganizationQuery
{
    /**
     * @param Builder $builder
     * @param string $identityAddress
     * @return Organization|Builder|mixed
     */
    public static function whereIsEmployee(
        Builder $builder,
        string $identityAddress
    ) {
        return $builder->where(static function(
            Builder $builder
        ) use ($identityAddress) {
            $builder->whereHas('employees', static function(
                Builder $builder
            ) use ($identityAddress) {
                $builder->where('employees.identity_address', $identityAddress);
            });
        });
    }

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
        return $builder->where(static function(
            Builder $builder
        ) use ($identityAddress, $permissions) {
            $builder->whereHas('employees', static function(
                Builder $builder
            ) use ($identityAddress, $permissions) {
                $builder->where('employees.identity_address', $identityAddress);

                $builder->whereHas('roles.permissions', static function(
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
        )->whereHas('fund_providers', static function(
            Builder $builder
        ) use ($voucher) {
            if ($voucher->type === Voucher::TYPE_PRODUCT) {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder, $voucher->fund_id, 'product', $voucher->product_id
                );
            } else {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    $voucher->fund_id,
                    $voucher->fund->isTypeBudget() ? 'budget' : 'subsidy'
                );
            }
        });
    }

    /**
     * @param Builder $query
     * @param Fund $fund
     * @return Builder
     */
    public static function whereIsExternalValidator(
        Builder $query,
        Fund $fund
    ): Builder {
        return $query->where(static function(Builder $builder) use ($fund) {
            $builder->where('is_validator', true);

            $builder->whereHas('validated_organizations.fund_criteria_validators', static function(
                Builder $builder
            ) use ($fund) {
                $builder->whereHas('fund_criterion', static function(
                    Builder $builder
                ) use ($fund) {
                    $builder->where('fund_id', $fund->id);
                });
            });
        });
    }

    /**
     * @param Builder $query
     * @param $implementation_id
     * @return Builder
     */
    public static function whereImplementationIdFilter(
        Builder $query,
        $implementation_id): Builder
    {
        return $query->whereHas('funds.fund_config', static function(
            Builder $builder
        ) use ($implementation_id) {
            $builder->whereIn('implementation_id', (array) $implementation_id);
        });
    }
}