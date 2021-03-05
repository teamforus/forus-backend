<?php

namespace App\Models;

use App\Models\Traits\HasTags;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\Forus\Session\Models\Session;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\MediaService\Models\Media;
use Carbon\Carbon;
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
 * @property string|null $description
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
 * @property bool $manage_provider_products
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BusinessType|null $business_type
 * @property-read Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|Organization[] $external_validators
 * @property-read int|null $external_validators_count
 * @property-read Collection|\App\Models\FundProviderInvitation[] $fund_provider_invitations
 * @property-read int|null $fund_provider_invitations_count
 * @property-read Collection|\App\Models\FundProvider[] $fund_providers
 * @property-read int|null $fund_providers_count
 * @property-read Collection|\App\Models\FundRequest[] $fund_requests
 * @property-read int|null $fund_requests_count
 * @property-read Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $funds_voucher_transactions
 * @property-read int|null $funds_voucher_transactions_count
 * @property-read string $description_html
 * @property-read Media|null $logo
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read Collection|\App\Models\Office[] $offices
 * @property-read int|null $offices_count
 * @property-read Collection|\App\Models\OrganizationValidator[] $organization_validators
 * @property-read int|null $organization_validators_count
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|\App\Models\Product[] $products_as_sponsor
 * @property-read int|null $products_as_sponsor_count
 * @property-read Collection|\App\Models\Product[] $products_provider
 * @property-read int|null $products_provider_count
 * @property-read Collection|\App\Models\Product[] $products_sponsor
 * @property-read int|null $products_sponsor_count
 * @property-read Collection|\App\Models\Fund[] $supplied_funds
 * @property-read int|null $supplied_funds_count
 * @property-read Collection|\App\Models\Fund[] $supplied_funds_approved
 * @property-read int|null $supplied_funds_approved_count
 * @property-read Collection|\App\Models\Fund[] $supplied_funds_approved_budget
 * @property-read int|null $supplied_funds_approved_budget_count
 * @property-read Collection|\App\Models\Fund[] $supplied_funds_approved_products
 * @property-read int|null $supplied_funds_approved_products_count
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read Collection|\App\Models\OrganizationValidator[] $validated_organizations
 * @property-read int|null $validated_organizations_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereBtw($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereBusinessTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereEmailPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereIban($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereIsProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereIsSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereIsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereKvk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereManageProviderProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization wherePhonePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereValidatorAutoAcceptFunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Organization whereWebsitePublic($value)
 * @mixin \Eloquent
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
        'validator_auto_accept_funds', 'manage_provider_products', 'description',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'btw'                           => 'string',
        'email_public'                  => 'boolean',
        'phone_public'                  => 'boolean',
        'website_public'                => 'boolean',
        'is_sponsor'                    => 'boolean',
        'is_provider'                   => 'boolean',
        'is_validator'                  => 'boolean',
        'manage_provider_products'      => 'boolean',
        'validator_auto_accept_funds'   => 'boolean',
    ];

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function searchQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = self::query();
        $has_products = $request->input('has_products');
        $fund_type    = $request->input('fund_type', 'budget');

        if ($request->input('is_employee', true)) {
            if (auth_address()) {
                $query = OrganizationQuery::whereIsEmployee($query, auth_address());
            } else {
                $query = $query->whereIn('id', []);
            }
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

        if ($q = $request->input('q')) {
            $query->where('name', 'LIKE', "%$q%");
        }

        if ($request->input('implementation', false)) {
            $query->whereHas('funds', static function(
                \Illuminate\Database\Eloquent\Builder $builder
            ) {
                $funds = Implementation::queryFundsByState('active')->pluck('id')->toArray();
                $builder->whereIn('funds.id', $funds);
            });
        }

        if ($has_products) {
            $query->whereHas('products', static function(\Illuminate\Database\Eloquent\Builder $builder) use ($fund_type) {
                $activeFunds = Implementation::activeFundsQuery()->where(
                    'type', $fund_type
                )->pluck('id')->toArray();

                // only in stock and not expired
                $builder = ProductQuery::inStockAndActiveFilter($builder);

                // only approved by at least one sponsor
                return ProductQuery::approvedForFundsFilter($builder, $activeFunds);
            });
        } else if ($has_products !== null) {
            $query->whereDoesntHave('products');
        }

        return $query;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     * @noinspection PhpUnused
     */
    public static function search(Request $request)
    {
        return self::searchQuery($request)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function funds(): HasMany
    {
        return $this->hasMany(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_as_sponsor(): HasMany
    {
        return $this->hasMany(Product::class, 'sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_provider(): HasMany
    {
        return $this->hasMany(Product::class)->whereNull('sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function products_sponsor(): HasMany
    {
        return $this->hasMany(Product::class)->whereNotNull('sponsor_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function validated_organizations(): HasMany
    {
        return $this->hasMany(OrganizationValidator::class, 'validator_organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organization_validators(): HasMany {
        return $this->hasMany(OrganizationValidator::class);
    }

    /**
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function external_validators(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            OrganizationValidator::class,
            'organization_id',
            'validator_organization_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function business_type(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function funds_voucher_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(VoucherTransaction::class, Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function fund_requests(): HasManyThrough
    {
        return $this->hasManyThrough(FundRequest::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function supplied_funds(): BelongsToMany
    {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_provider_invitations(): HasMany
    {
        return $this->hasMany(FundProviderInvitation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function supplied_funds_approved(): BelongsToMany
    {
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
     * @noinspection PhpUnused
     */
    public function supplied_funds_approved_budget(): BelongsToMany
    {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where(function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('fund_providers.allow_budget', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function supplied_funds_approved_products(): BelongsToMany
    {
        return $this->belongsToMany(
            Fund::class,
            'fund_providers'
        )->where(static function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('fund_providers.allow_products', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_providers(): HasMany
    {
        return $this->hasMany(FundProvider::class);
    }
    /**
     * Get organization logo
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'organization_logo'
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function vouchers(): HasManyThrough
    {
        return $this->hasManyThrough(Voucher::class, Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     * @noinspection PhpUnused
     */
    public function getLastActivity(): ?Carbon
    {
        /** @var Session|null $session */
        $session = Session::whereIn(
            'identity_address',
            $this->employees->pluck('identity_address')
        )->latest('last_activity_at')->first();

        return $session ? $session->last_activity_at : null;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return resolve('markdown')->convertToHtml($this->description ?? '');
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

    /**
     * @param $fund_id
     * @return Fund|null
     */
    public function findFund($fund_id): ?Fund {
        /** @var Fund|null $fund */
        $fund = $fund_id ? $this->funds()->where('funds.id', '=', $fund_id)->first() : null;
        return $fund;
    }

    /**
     * @return array
     */
    public function productsMeta(): array
    {
        return [
            'total_archived' => Product::onlyTrashed()->where('organization_id', $this->id)->count(),
            'total_provider' => Product::whereOrganizationId($this->id)->whereNull(
                'sponsor_organization_id'
            )->count(),
            'total_sponsor' => Product::whereOrganizationId($this->id)->whereNotNull(
                'sponsor_organization_id'
            )->count(),
        ];
    }
}
