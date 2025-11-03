<?php

namespace App\Scopes\Builders;

use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Database\Query\Builder as QBuilder;

class OrganizationQuery
{
    /**
     * @param Builder|Relation|Organization $builder
     * @param string|array $identityAddress
     * @return Builder|Relation|Organization
     */
    public static function whereIsEmployee(
        Builder|Relation|Organization $builder,
        string|array $identityAddress,
    ): Builder|Relation|Organization {
        return $builder->where(static function (Builder $builder) use ($identityAddress) {
            $builder->whereHas('employees', static function (Builder $builder) use ($identityAddress) {
                $builder->whereIn('identity_address', (array) $identityAddress);
            });
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param string|array $identityAddress
     * @param string|array $permissions
     * @return Builder|Relation|Organization
     */
    public static function whereHasPermissions(
        Builder|Relation|Organization $builder,
        string|array $identityAddress,
        string|array $permissions,
    ): Builder|Relation|Organization {
        return $builder->where(static function (Builder $builder) use ($identityAddress, $permissions) {
            $builder->whereHas('employees', static function (Builder $builder) use ($identityAddress, $permissions) {
                $builder->where('employees.identity_address', $identityAddress);

                $builder->whereHas('roles.permissions', static function (Builder $builder) use ($permissions) {
                    $builder->whereIn('permissions.key', (array) $permissions);
                });
            })->orWhere('organizations.identity_address', $identityAddress);
        });
    }

    /**
     * @param Builder|Relation|Organization $query
     * @param string $identity_address
     * @param Voucher $voucher
     * @return Builder|Relation|Organization
     */
    public static function whereHasPermissionToScanVoucher(
        Builder|Relation|Organization $query,
        string $identity_address,
        Voucher $voucher,
    ): Builder|Relation|Organization {
        $query = self::whereHasPermissions($query, $identity_address, Permission::SCAN_VOUCHERS);

        return $query->whereHas('fund_providers', static function (Builder $builder) use ($voucher) {
            if ($voucher->isProductType()) {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    $voucher->fund_id,
                    'allow_products',
                    $voucher->product_id
                );
            } else {
                FundProviderQuery::whereApprovedForFundsFilter(
                    $builder,
                    $voucher->fund_id,
                    'allow_budget',
                );
            }
        });
    }

    /**
     * @param Builder|Relation|Organization $query
     * @param Organization $sponsor
     * @return Builder|Relation|Organization
     */
    public static function whereIsProviderOrganization(
        Builder|Relation|Organization $query,
        Organization $sponsor,
    ): Builder|Relation|Organization {
        return $query->whereHas('fund_providers.fund', function (Builder $builder) use ($sponsor) {
            $builder->where('organization_id', $sponsor->id);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param array $postcodes
     * @return Builder|Relation|Organization
     */
    public static function whereHasPostcodes(
        Builder|Relation|Organization $builder,
        array $postcodes,
    ): Builder|Relation|Organization {
        return $builder->whereHas('offices', function (Builder $builder) use ($postcodes) {
            $builder->whereIn('postcode_number', $postcodes);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param array $businessTypes
     * @return Builder|Relation|Organization
     */
    public static function whereHasBusinessType(
        Builder|Relation|Organization $builder,
        array $businessTypes,
    ): Builder|Relation|Organization {
        return $builder->whereHas('business_type', function (Builder $builder) use ($businessTypes) {
            $builder->whereIn('id', $businessTypes);
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @param string|null $stateGroup
     * @return Builder|Relation|Organization
     */
    public static function whereGroupState(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
        ?string $stateGroup = null,
    ): Builder|Relation|Organization {
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
     * @return Builder|Relation|Organization
     */
    public static function whereGroupStatePending(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder|Relation|Organization {
        $fundsBuilder = FundQuery::whereActiveFilter($sponsorOrganization->funds());

        return $builder->whereHas('fund_providers', function (Builder $query) use ($fundsBuilder) {
            $query->where('state', FundProvider::STATE_PENDING);

            $query->whereHas('fund', function (Builder|Fund $builder) use ($fundsBuilder) {
                $builder->whereIn('id', $fundsBuilder->select('id'));
                $builder->where(fn (Builder|Fund $builder) => FundQuery::whereActiveFilter($builder));
            });
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param Organization $sponsorOrganization
     * @return Builder|Relation|Organization
     */
    public static function whereGroupStateActive(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder|Relation|Organization {
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
     * @return Builder|Relation|Organization
     */
    public static function whereGroupStateRejected(
        Builder|Relation|Organization $builder,
        Organization $sponsorOrganization,
    ): Builder|Relation|Organization {
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

    /**
     * @param Builder|Relation|Organization $query
     * @param string|null $q
     * @return Builder|Relation|Organization
     */
    public static function queryFilterPublic(
        Builder|Relation|Organization $query,
        string $q = null,
    ): Builder|Relation|Organization {
        return $query->where(function (Builder $builder) use ($q) {
            $builder->where('name', 'LIKE', "%$q%");
            $builder->orWhere('description_text', 'LIKE', "%$q%");

            $builder->orWhere(function (Builder $builder) use ($q) {
                $builder->where('email_public', true);
                $builder->where('email', 'LIKE', "%$q%");
            });

            $builder->orWhere(function (Builder $builder) use ($q) {
                $builder->where('phone_public', true);
                $builder->where('phone', 'LIKE', "%$q%");
            });

            $builder->orWhere(function (Builder $builder) use ($q) {
                $builder->where('website_public', true);
                $builder->where('website', 'LIKE', "%$q%");
            });
        });
    }

    /**
     * @param Builder|Relation|Organization $builder
     * @param string|null $q
     * @return Builder|Relation|Organization
     */
    public static function queryFilterProviders(
        Builder|Relation|Organization $builder,
        string $q = null,
    ): Builder|Relation|Organization {
        return $builder->where(static function (Builder $builder) use ($q) {
            $like = '%' . $q . '%';
            $builder->where('name', 'LIKE', $like);

            $builder->orWhere(static function (Builder $builder) use ($like) {
                $builder->where('email_public', true);
                $builder->where('email', 'LIKE', $like);
            })->orWhere(static function (Builder $builder) use ($like) {
                $builder->where('phone_public', true);
                $builder->where('phone', 'LIKE', $like);
            })->orWhere(static function (Builder $builder) use ($like) {
                $builder->where('website_public', true);
                $builder->where('website', 'LIKE', $like);
            });

            $builder->orWhereHas('business_type.translations', static function (
                Builder $builder
            ) use ($like) {
                $builder->where('business_type_translations.name', 'LIKE', $like);
            });

            $builder->orWhereHas('offices', static function (
                Builder $builder
            ) use ($like) {
                $builder->where(static function (Builder $query) use ($like) {
                    $query->where(
                        'address',
                        'LIKE',
                        $like
                    );
                });
            });
        });
    }

    /**
     * @param $identityAddress string
     * @param string|array|bool $permissions
     * @return Builder
     */
    public static function queryByIdentityPermissions(
        string $identityAddress,
        string|array|bool $permissions = false
    ): Builder {
        $permissions = $permissions === false ? false : (array) $permissions;

        /**
         * Query all the organizations where identity_address has permissions
         * or is the creator.
         */
        return Organization::where(static function (Builder $builder) use (
            $identityAddress,
            $permissions
        ) {
            return $builder->whereIn('id', function (QBuilder $query) use (
                $identityAddress,
                $permissions
            ) {
                $query->select(['organization_id'])->from((new Employee())->getTable())->where([
                    'identity_address' => $identityAddress,
                ])->whereNull('deleted_at')->whereIn('id', function (QBuilder $query) use ($permissions) {
                    $query->select('employee_id')->from(
                        (new EmployeeRole())->getTable()
                    )->whereIn('role_id', function (QBuilder $query) use ($permissions) {
                        $query->select(['id'])->from((new Role())->getTable())->whereIn('id', function (
                            QBuilder $query
                        ) use ($permissions) {
                            return $query->select(['role_id'])->from(
                                (new RolePermission())->getTable()
                            )->whereIn('permission_id', function (QBuilder $query) use ($permissions) {
                                $query->select('id')->from((new Permission())->getTable());

                                // allow any permission
                                if ($permissions !== false) {
                                    $query->whereIn('key', $permissions);
                                }

                                return $query;
                            });
                        })->get();
                    });
                });
            })->orWhere('identity_address', $identityAddress);
        });
    }
}
