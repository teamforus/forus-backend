<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Class OrganizationQuery
 * @package App\Scopes\Builders
 */
class OrganizationQuery
{
    /**
     * @param Builder $builder
     * @param string|array $identityAddress
     * @return Builder
     */
    public static function whereIsEmployee(
        Builder $builder,
        string $identityAddress
    ): Builder {
        return $builder->where(static function(Builder $builder) use ($identityAddress) {
            $builder->whereHas('employees', static function(Builder $builder) use ($identityAddress) {
                $builder->whereIn('employees.identity_address', (array) $identityAddress);
            });
        });
    }

    /**
     * @param Builder $builder
     * @param string $identityAddress
     * @param $permissions
     * @return Builder
     */
    public static function whereHasPermissions(
        Builder $builder,
        string $identityAddress,
        $permissions
    ): Builder {
        return $builder->where(static function(Builder $builder) use ($identityAddress, $permissions) {
            $builder->whereHas('employees', static function(Builder $builder) use ($identityAddress, $permissions) {
                $builder->where('employees.identity_address', $identityAddress);

                $builder->whereHas('roles.permissions', static function(Builder $builder) use ($permissions) {
                    $builder->whereIn('permissions.key', (array) $permissions);
                });
            })->orWhere('organizations.identity_address', $identityAddress);
        });
    }

    /**
     * @param Builder $query
     * @param string $identity_address
     * @param Voucher $voucher
     * @return Builder
     */
    public static function whereHasPermissionToScanVoucher(
        Builder $query,
        string $identity_address,
        Voucher $voucher
    ): Builder {
        $query = self::whereHasPermissions($query, $identity_address,'scan_vouchers');

        return $query->whereHas('fund_providers', static function(Builder $builder) use ($voucher) {
            if ($voucher->isProductType()) {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    $voucher->fund_id,
                    'product',
                    $voucher->product_id
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
    public static function whereIsExternalValidator(Builder $query, Fund $fund): Builder
    {
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
     * @param Organization $sponsor
     * @return Builder
     */
    public static function whereIsProviderOrganization(Builder $query, Organization $sponsor): Builder
    {
        return $query->whereHas('fund_providers.fund', function(Builder $builder) use ($sponsor) {
            $builder->where('organization_id', $sponsor->id);
        });
    }

    /**
     * @param Builder $builder
     * @param array $postcodes
     * @return Builder
     */
    public static function whereHasPostcodes(Builder $builder, array $postcodes): Builder
    {
        return $builder->whereHas('offices', function(Builder $builder) use ($postcodes) {
            $builder->whereIn('postcode_number', $postcodes);
        });
    }

    /**
     * @param Builder $query
     * @param Organization $sponsor
     * @param array $options fields: $order_by: name/application_date, $order_dir: asc/desc
     * @return Builder
     */
    public static function orderProvidersBy(
        Builder $query,
        Organization $sponsor,
        array $options = []
    ): Builder {
        switch (Arr::get($options, 'order_by', 'name')) {
            case 'application_date': {
                $fundsQuery = $sponsor->funds()->where('archived', false)->select('id')->getQuery();
                $providersQuery = FundProvider::whereIn('fund_id', $fundsQuery);
                $providersQuery->selectRaw('`organization_id`, MAX(`created_at`) as `last_created_at`');
                $providersQuery->groupBy('organization_id');

                $query->leftJoinSub($providersQuery, 'fund_providers', 'fund_providers.organization_id', 'organizations.id');
                $query->orderBy('fund_providers.last_created_at', Arr::get($options, 'order_dir', 'desc'));
                $query->select('organizations.*');
            } break;
            case 'name': {
                $query->orderBy('name', Arr::get($options, 'order_dir', 'asc'));
            } break;
        }

        return $query;
    }
}