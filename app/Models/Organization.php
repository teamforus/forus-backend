<?php

namespace App\Models;

use App\Models\Traits\HasTags;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * App\Models\Organization
 *
 * @property int $id
 * @property string|null $identity_address
 * @property string $name
 * @property string $iban
 * @property string $email
 * @property bool $email_public
 * @property string $phone
 * @property bool $phone_public
 * @property string $kvk
 * @property string $btw
 * @property string|null $website
 * @property bool $website_public
 * @property int|null $business_type_id
 * @property bool $is_sponsor
 * @property bool $is_provider
 * @property bool $is_validator
 * @property bool $validator_auto_accept_funds
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BusinessType|null $business_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $external_validators
 * @property-read int|null $external_validators_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderInvitation[] $fund_provider_invitations
 * @property-read int|null $fund_provider_invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $funds_voucher_transactions
 * @property-read int|null $funds_voucher_transactions_count
 * @property-read \App\Services\MediaService\Models\Media|null $logo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Office[] $offices
 * @property-read int|null $offices_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrganizationValidator[] $organization_validators
 * @property-read int|null $organization_validators_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $supplied_funds
 * @property-read int|null $supplied_funds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $supplied_funds_approved
 * @property-read int|null $supplied_funds_approved_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $supplied_funds_approved_budget
 * @property-read int|null $supplied_funds_approved_budget_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $supplied_funds_approved_products
 * @property-read int|null $supplied_funds_approved_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrganizationValidator[] $validated_organizations
 * @property-read int|null $validated_organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBtw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereBusinessTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereEmailPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereIsProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereIsSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereIsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereKvk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization wherePhonePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereValidatorAutoAcceptFunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereWebsitePublic($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Organization whereDescription($value)
 */
class Organization extends Model
{
    use HasMedia, HasTags, HasLogs, HasDigests;

    public const GENERIC_KVK = "00000000";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'iban', 'email', 'email_public',
        'phone', 'phone_public', 'kvk', 'btw', 'website', 'website_public',
        'business_type_id', 'is_sponsor', 'is_provider', 'is_validator',
        'validator_auto_accept_funds', 'description'
    ];

    protected $casts = [
        'btw'                           => 'string',
        'email_public'                  => 'boolean',
        'phone_public'                  => 'boolean',
        'website_public'                => 'boolean',
        'is_sponsor'                    => 'boolean',
        'is_provider'                   => 'boolean',
        'is_validator'                  => 'boolean',
        'validator_auto_accept_funds'   => 'boolean'
    ];

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function searchQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = self::query();

        if ($request->input('is_employee', true)) {
            $query = OrganizationQuery::whereIsEmployee($query, auth_address());
        }

        if ($request->has('is_sponsor')) {
            $query->where($request->only('is_sponsor'));
        }

        if ($request->has('is_provider')) {
            $query->where($request->only('is_provider'));
        }

        if ($request->has('is_validator')) {
            $query->where($request->only('is_validator'));
        }

        if ($request->input('implementation', false)) {
            $query->whereHas('funds', static function(
                \Illuminate\Database\Eloquent\Builder $builder
            ) {
                $funds = Implementation::queryFundsByState('active')->pluck('id')->toArray();
                $builder->whereIn('funds.id', $funds);
            });
        }

        return $query;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     */
    public static function search(Request $request)
    {
        return self::searchQuery($request)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function funds(): HasMany {
        return $this->hasMany(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validated_organizations(): HasMany {
        return $this->hasMany(OrganizationValidator::class, 'validator_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organization_validators(): HasMany {
        return $this->hasMany(OrganizationValidator::class);
    }

    public function external_validators(): BelongsToMany {
        return $this->belongsToMany(
            self::class,
            OrganizationValidator::class,
            'organization_id',
            'validator_organization_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offices(): HasMany {
        return $this->hasMany(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business_type(): BelongsTo {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voucher_transactions(): HasMany {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds_voucher_transactions(): HasManyThrough {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function fund_requests(): HasManyThrough {
        return $this->hasManyThrough(FundRequest::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds(): BelongsToMany {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_invitations(): HasMany {
        return $this->hasMany(FundProviderInvitation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds_approved(): BelongsToMany {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where(function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('fund_providers.allow_budget', true);
            $builder->orWhere(function(\Illuminate\Database\Eloquent\Builder $builder) {
                $builder->where('fund_providers.allow_products', true);
                $builder->orWhere('fund_providers.allow_some_products', true);
            });
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds_approved_budget(): BelongsToMany {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where(function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('fund_providers.allow_budget', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supplied_funds_approved_products(): BelongsToMany {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where(static function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('fund_providers.allow_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_providers(): HasMany {
        return $this->hasMany(FundProvider::class);
    }
    /**
     * Get organization logo
     * @return MorphOne
     */
    public function logo(): MorphOne {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'organization_logo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vouchers(): HasManyThrough {
        return $this->hasManyThrough(Voucher::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany {
        return $this->hasMany(Employee::class);
    }

    /**
     * @param $role
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeesOfRoleQuery($role) {
        return $this->employees()->whereHas('roles', function(
            \Illuminate\Database\Eloquent\Builder $query
        ) use ($role) {
            $query->whereIn('key', (array) $role);
        });
    }

    /**
     * @param $permission
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employeesWithPermissionsQuery($permission) {
        return $this->employees()->whereHas('roles.permissions', static function(
            \Illuminate\Database\Eloquent\Builder $query
        ) use ($permission) {
            $query->whereIn('permissions.key', (array) $permission);
        });
    }

    /**
     * @param string|array $role
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection|\Illuminate\Database\Eloquent\Relations\HasMany[]
     */
    public function employeesOfRole($role) {
        return $this->employeesOfRoleQuery($role)->get();
    }

    /**
     * @param string|array $permission
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection|\Illuminate\Database\Eloquent\Relations\HasMany[]
     */
    public function employeesWithPermissions($permission) {
        return $this->employeesWithPermissionsQuery($permission)->get();
    }

    /**
     * Returns identity organization roles
     *
     * @param $identityAddress
     * @return Collection|\Illuminate\Support\Collection
     */
    public function identityRoles($identityAddress) {
        /** @var Employee $employee */
        $employee = $this->employees()->where('identity_address', $identityAddress)->first();

        return $employee->roles ?? collect([]);
    }

    /**
     * Returns identity organization permissions
     * @param $identityAddress
     * @return \Illuminate\Support\Collection
     */
    public function identityPermissions(
        $identityAddress
    ): \Illuminate\Support\Collection {
        if (strcmp($identityAddress, $this->identity_address) === 0) {
            return Permission::allMemCached();
        }

        $roles = $this->identityRoles($identityAddress);

        return $roles->pluck('permissions')->flatten()->unique('id');
    }

    /**
     * Check if identity is organization employee
     * @param string|null $identity_address string
     * @return bool
     */
    public function isEmployee(?string $identity_address = null): bool {
        return $identity_address &&
            $this->employees()->whereIn('identity_address', (array) $identity_address)->exists();
    }

    /**
     * @param string|null $identityAddress string
     * @param array|string $permissions
     * @param $all boolean
     * @return bool
     */
    public function identityCan(
        string $identityAddress = null,
        $permissions = [],
        $all = true
    ): bool {
        if (!$identityAddress) {
            return false;
        }

        // as owner of the organization you don't have any restrictions
        if (strcmp($identityAddress, $this->identity_address) === 0) {
            return true;
        }

        // retrieving the list of all the permissions that identity have
        $identityPermissionKeys = $this->identityPermissions(
            $identityAddress
        )->pluck('key');

        // convert string to array
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        if (!$all) {
            return $identityPermissionKeys->intersect($permissions)->count() > 0;
        }

        // check if all the requirements are satisfied
        return $identityPermissionKeys->intersect($permissions)->count() === count($permissions);
    }

    /**
     * @param $identityAddress string
     * @param bool|array|string $roles
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryByIdentityRole (
        string $identityAddress,
        $roles = false
    ): \Illuminate\Database\Eloquent\Builder {
        $roles = (array) $roles;

        return self::query()->whereIn('id', function(Builder $query) use ($identityAddress, $roles) {
            $query->select(['organization_id'])->from((new Employee)->getTable())->where([
                'identity_address' => $identityAddress
            ])->whereIn('id', function (Builder $query) use ($roles) {
                $query->select('employee_id')->from((new EmployeeRole)->getTable())->whereIn('role_id', function (Builder $query) use ($roles) {
                    $query->select(['id'])->from((new Role)->getTable())->whereIn('key', $roles)->get();
                });
            });
        })->orWhere('identity_address', $identityAddress);
    }

    /**
     * @param $identityAddress string
     * @param string|array|bool $permissions
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryByIdentityPermissions (
        string $identityAddress,
        $permissions = false
    ): \Illuminate\Database\Eloquent\Builder {
        // convert string to array
        if (is_string($permissions)) {
            $permissions = (array) $permissions;
        }

        /**
         * Query all the organizations where identity_address has permissions
         * or is the creator
         */
        return self::query()->where(static function(\Illuminate\Database\Eloquent\Builder $builder) use (
            $identityAddress, $permissions
        ) {
            return $builder->whereIn('id', function(Builder $query) use (
                $identityAddress, $permissions
            ) {
                $query->select(['organization_id'])->from((new Employee)->getTable())->where([
                    'identity_address' => $identityAddress
                ])->whereNull('deleted_at')->whereIn('id', function (Builder $query) use ($permissions) {
                    $query->select('employee_id')->from(
                        (new EmployeeRole)->getTable()
                    )->whereIn('role_id', function (Builder $query) use ($permissions) {
                        $query->select(['id'])->from((new Role)->getTable())->whereIn('id', function (
                            Builder $query
                        )  use ($permissions) {
                            return $query->select(['role_id'])->from(
                                (new RolePermission)->getTable()
                            )->whereIn('permission_id', function (Builder $query) use ($permissions) {
                                $query->select('id')->from((new Permission)->getTable());

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

    /**
     * @param $attributes
     * @return Fund|\Illuminate\Database\Eloquent\Model
     */
    public function createFund($attributes) {
        return Fund::create(array_merge([
            'organization_id' => $this->id,
        ], $attributes));
    }

    /**
     * @param Organization $validatorOrganization
     */
    public function detachExternalValidator(
        Organization $validatorOrganization
    ): void {
        /** @var Fund[] $fundsAffected */
        $fundsAffected = FundQuery::whereExternalValidatorFilter(
            $this->funds()->getQuery(),
            $validatorOrganization->id
        )->get();

        foreach ($fundsAffected as $fund) {
            $fund->detachExternalValidator($validatorOrganization);
        }

        $this->organization_validators()->where([
            'validator_organization_id' => $validatorOrganization->id,
        ])->delete();
    }

    /**
     * @param string $identity_address
     * @return Model|Employee|null|object
     */
    public function findEmployee(
        string $identity_address
    ) {
        return $this->employees()->where(compact('identity_address'))->first();
    }
}
