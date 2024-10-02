<?php


namespace App\Scopes\Builders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        string|array $identityAddress,
    ): Builder {
        return $builder->where(static function(Builder $builder) use ($identityAddress) {
            $builder->whereHas('employees', static function(Builder $builder) use ($identityAddress) {
                $builder->whereIn('employees.identity_address', (array) $identityAddress);
            });
        });
    }

    /**
     * @param Builder $builder
     * @param string|array $identityAddress
     * @param string|array $permissions
     * @return Builder
     */
    public static function whereHasPermissions(
        Builder $builder,
        string|array $identityAddress,
        string|array $permissions,
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
     * @param Builder $builder
     * @param array $businessTypes
     * @return Builder
     */
    public static function whereHasBusinessType(Builder $builder, array $businessTypes): Builder
    {
        return $builder->whereHas('business_type', function(Builder $builder) use ($businessTypes) {
            $builder->whereIn('id', $businessTypes);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @param string|null $stateGroup
     * @return Builder
     */
    public static function whereGroupState(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
        ?string $stateGroup = null
    ): Builder {
        return match ($stateGroup) {
            'pending' => self::whereGroupStatePending($builder, $sponsorOrganization),
            'active' => self::whereGroupStateActive($builder, $sponsorOrganization),
            'rejected' => self::whereGroupStateRejected($builder, $sponsorOrganization),
            default => $builder,
        };
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @return Builder
     */
    public static function whereGroupStatePending(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder {
        $fundsBuilder = FundQuery::whereActiveFilter($sponsorOrganization->funds());

        return $builder->whereHas('fund_providers', function (Builder $query) use ($fundsBuilder) {
            $query->where('state', FundProvider::STATE_PENDING);

            $query->whereHas('fund', function(Builder|Fund $builder) use ($fundsBuilder) {
                $builder->whereIn('id', $fundsBuilder->select('id'));
                $builder->where(fn (Builder|Fund $builder) => FundQuery::whereActiveFilter($builder));
            });
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @return Builder
     */
    public static function whereGroupStateActive(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder {
        $fundsBuilder = FundQuery::whereActiveFilter($sponsorOrganization->funds());

        return $builder->where(function (Builder $builder) use ($fundsBuilder) {
            $builder->whereHas('fund_providers', function (Builder $query) use ($fundsBuilder) {
                $query->where('state', FundProvider::STATE_ACCEPTED);
                $query->whereIn('fund_id', $fundsBuilder->select('id'));
            });

            $builder->whereDoesntHave('fund_providers', function (Builder $query) use ($fundsBuilder) {
                $query->where('state', FundProvider::STATE_PENDING);
                $query->whereIn('fund_id', $fundsBuilder->select('id'));
            });
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @return Builder
     */
    public static function whereGroupStateRejected(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder {
        return $builder->where(function (Builder $builder) use ($sponsorOrganization) {
            $builder->where(function (Builder $builder) use ($sponsorOrganization) {
                $fundsBuilder = clone FundQuery::whereActiveFilter($sponsorOrganization->funds());

                $builder->whereHas('fund_providers', function (Builder $query) use ($fundsBuilder) {
                    $query->where('state', FundProvider::STATE_REJECTED);
                    $query->whereIn('fund_id', $fundsBuilder->select('id'));
                });

                $builder->whereDoesntHave('fund_providers', function (Builder $query) use ($fundsBuilder) {
                    $query->where('state', FundProvider::STATE_ACCEPTED);
                    $query->whereIn('fund_id', $fundsBuilder->select('id'));
                });

                $builder->whereDoesntHave('fund_providers', function (Builder $query) use ($fundsBuilder) {
                    $query->where('state', FundProvider::STATE_PENDING);
                    $query->whereIn('fund_id', $fundsBuilder->select('id'));
                });
            });

            $builder->orWhereHas('fund_providers', function (Builder $query) use ($sponsorOrganization) {
                $fundsBuilder = clone FundQuery::whereNotActiveFilter($sponsorOrganization->funds());

                $query->whereIn('fund_id', $fundsBuilder->select('id'));
            });
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
                $query->orderBy('name');
                $query->orderBy('id', 'desc');
                $query->select('organizations.*');
            } break;
            case 'name': {
                $query->orderBy('name', Arr::get($options, 'order_dir', 'asc'));
            } break;
        }

        return $query;
    }
}