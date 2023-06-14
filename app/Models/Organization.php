<?php

namespace App\Models;

use App\Events\Employees\EmployeeCreated;
use App\Http\Requests\BaseFormRequest;
use App\Models\Traits\HasTags;
use App\Scopes\Builders\EmployeeQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\BankService\Models\Bank;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\Forus\Session\Models\Session;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\MediaService\Models\Media;
use App\Traits\HasMarkdownDescription;
use App\Statistics\Funds\FinancialStatisticQueries;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\Organization
 *
 * @property int $id
 * @property string|null $identity_address
 * @property string $name
 * @property string|null $description
 * @property string|null $description_text
 * @property string $iban
 * @property string $email
 * @property bool $email_public
 * @property string $phone
 * @property bool $phone_public
 * @property string $kvk
 * @property string $btw
 * @property string|null $website
 * @property bool $website_public
 * @property string|null $low_balance_email
 * @property int|null $business_type_id
 * @property bool $is_sponsor
 * @property bool $is_provider
 * @property bool $is_validator
 * @property bool $validator_auto_accept_funds
 * @property bool $reservations_budget_enabled
 * @property bool $reservations_subsidy_enabled
 * @property bool $reservations_auto_accept
 * @property bool $manage_provider_products
 * @property bool $backoffice_available
 * @property bool $allow_batch_reservations
 * @property bool $allow_custom_fund_notifications
 * @property bool $allow_budget_fund_limits
 * @property bool $allow_manual_bulk_processing
 * @property int $allow_fund_request_record_edit
 * @property bool $pre_approve_external_funds
 * @property int $provider_throttling_value
 * @property string $fund_request_resolve_policy
 * @property bool $bsn_enabled
 * @property int $validator_records_edit_enabled
 * @property string|null $bank_cron_time
 * @property int $show_provider_transactions
 * @property string $reservation_phone
 * @property string $reservation_address
 * @property string $reservation_requester_birth_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankConnection|null $bank_connection_active
 * @property-read Collection|\App\Models\BankConnection[] $bank_connections
 * @property-read int|null $bank_connections_count
 * @property-read \App\Models\BusinessType|null $business_type
 * @property-read Collection|\App\Services\EventLogService\Models\Digest[] $digests
 * @property-read int|null $digests_count
 * @property-read Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read Collection|\App\Models\Employee[] $employees_with_trashed
 * @property-read int|null $employees_with_trashed_count
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
 * @property-read string $description_html
 * @property-read \App\Models\Identity|null $identity
 * @property-read Collection|\App\Models\Implementation[] $implementations
 * @property-read int|null $implementations_count
 * @property-read Session|null $last_employee_session
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
 * @property-read Collection|\App\Models\ReimbursementCategory[] $reimbursement_categories
 * @property-read int|null $reimbursement_categories_count
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
 * @property-read Collection|\App\Models\VoucherTransactionBulk[] $voucher_transaction_bulks
 * @property-read int|null $voucher_transaction_bulks_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $voucher_transactions
 * @property-read int|null $voucher_transactions_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static EloquentBuilder|Organization newModelQuery()
 * @method static EloquentBuilder|Organization newQuery()
 * @method static EloquentBuilder|Organization query()
 * @method static EloquentBuilder|Organization whereAllowBatchReservations($value)
 * @method static EloquentBuilder|Organization whereAllowBudgetFundLimits($value)
 * @method static EloquentBuilder|Organization whereAllowCustomFundNotifications($value)
 * @method static EloquentBuilder|Organization whereAllowFundRequestRecordEdit($value)
 * @method static EloquentBuilder|Organization whereAllowManualBulkProcessing($value)
 * @method static EloquentBuilder|Organization whereBackofficeAvailable($value)
 * @method static EloquentBuilder|Organization whereBankCronTime($value)
 * @method static EloquentBuilder|Organization whereBsnEnabled($value)
 * @method static EloquentBuilder|Organization whereBtw($value)
 * @method static EloquentBuilder|Organization whereBusinessTypeId($value)
 * @method static EloquentBuilder|Organization whereCreatedAt($value)
 * @method static EloquentBuilder|Organization whereDescription($value)
 * @method static EloquentBuilder|Organization whereDescriptionText($value)
 * @method static EloquentBuilder|Organization whereEmail($value)
 * @method static EloquentBuilder|Organization whereEmailPublic($value)
 * @method static EloquentBuilder|Organization whereFundRequestResolvePolicy($value)
 * @method static EloquentBuilder|Organization whereIban($value)
 * @method static EloquentBuilder|Organization whereId($value)
 * @method static EloquentBuilder|Organization whereIdentityAddress($value)
 * @method static EloquentBuilder|Organization whereIsProvider($value)
 * @method static EloquentBuilder|Organization whereIsSponsor($value)
 * @method static EloquentBuilder|Organization whereIsValidator($value)
 * @method static EloquentBuilder|Organization whereKvk($value)
 * @method static EloquentBuilder|Organization whereLowBalanceEmail($value)
 * @method static EloquentBuilder|Organization whereManageProviderProducts($value)
 * @method static EloquentBuilder|Organization whereName($value)
 * @method static EloquentBuilder|Organization wherePhone($value)
 * @method static EloquentBuilder|Organization wherePhonePublic($value)
 * @method static EloquentBuilder|Organization wherePreApproveExternalFunds($value)
 * @method static EloquentBuilder|Organization whereProviderThrottlingValue($value)
 * @method static EloquentBuilder|Organization whereReservationAddress($value)
 * @method static EloquentBuilder|Organization whereReservationPhone($value)
 * @method static EloquentBuilder|Organization whereReservationRequesterBirthDate($value)
 * @method static EloquentBuilder|Organization whereReservationsAutoAccept($value)
 * @method static EloquentBuilder|Organization whereReservationsBudgetEnabled($value)
 * @method static EloquentBuilder|Organization whereReservationsSubsidyEnabled($value)
 * @method static EloquentBuilder|Organization whereShowProviderTransactions($value)
 * @method static EloquentBuilder|Organization whereUpdatedAt($value)
 * @method static EloquentBuilder|Organization whereValidatorAutoAcceptFunds($value)
 * @method static EloquentBuilder|Organization whereValidatorRecordsEditEnabled($value)
 * @method static EloquentBuilder|Organization whereWebsite($value)
 * @method static EloquentBuilder|Organization whereWebsitePublic($value)
 * @mixin \Eloquent
 */
class Organization extends BaseModel
{
    use HasMedia, HasTags, HasLogs, HasDigests, HasMarkdownDescription;

    public const GENERIC_KVK = "00000000";

    public const FUND_REQUEST_POLICY_MANUAL = 'apply_manually';
    public const FUND_REQUEST_POLICY_AUTO_REQUESTED = 'apply_auto_requested';
    public const FUND_REQUEST_POLICY_AUTO_AVAILABLE = 'apply_auto_available';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'iban', 'email', 'email_public',
        'phone', 'phone_public', 'kvk', 'btw', 'website', 'website_public',
        'business_type_id', 'is_sponsor', 'is_provider', 'is_validator',
        'validator_auto_accept_funds', 'manage_provider_products', 'description', 'description_text',
        'backoffice_available', 'reservations_budget_enabled', 'reservations_subsidy_enabled',
        'reservations_auto_accept', 'bsn_enabled', 'allow_custom_fund_notifications',
        'reservation_phone', 'reservation_address', 'reservation_birth_date',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'btw'                                   => 'string',
        'email_public'                          => 'boolean',
        'phone_public'                          => 'boolean',
        'website_public'                        => 'boolean',
        'is_sponsor'                            => 'boolean',
        'is_provider'                           => 'boolean',
        'is_validator'                          => 'boolean',
        'backoffice_available'                  => 'boolean',
        'manage_provider_products'              => 'boolean',
        'validator_auto_accept_funds'           => 'boolean',
        'reservations_budget_enabled'           => 'boolean',
        'reservations_subsidy_enabled'          => 'boolean',
        'reservations_auto_accept'              => 'boolean',
        'allow_batch_reservations'              => 'boolean',
        'allow_custom_fund_notifications'       => 'boolean',
        'allow_budget_fund_limits'              => 'boolean',
        'allow_manual_bulk_processing'          => 'boolean',
        'pre_approve_external_funds'            => 'boolean',
        'bsn_enabled'                           => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function initialFundState(?string $type = 'budget'): string
    {
        if ($type === Fund::TYPE_EXTERNAL && $this->pre_approve_external_funds) {
            return Fund::STATE_PAUSED;
        }

        return Fund::STATE_WAITING;
    }

    /**
     * @return HasMany
     */
    public function reimbursement_categories(): HasMany {
        return $this->hasMany(ReimbursementCategory::class);
    }

    /**
     * @param BaseFormRequest $request
     * @param EloquentBuilder|null $builder
     * @return EloquentBuilder
     */
    public static function searchQuery(
        BaseFormRequest $request,
        EloquentBuilder $builder = null
    ): EloquentBuilder {
        $query = $builder ?: self::query();
        $fund_type = $request->input('fund_type', 'budget');
        $has_products = $request->input('has_products');
        $has_reservations = $request->input('has_reservations');

        if ($request->input('is_employee', true)) {
            if ($request->isAuthenticated()) {
                $query = OrganizationQuery::whereIsEmployee($query, $request->auth_address());
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
            return $query->where(function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
                $builder->orWhere('description_text', 'LIKE', "%$q%");
            });
        }

        if ($request->input('implementation', false)) {
            $query->whereHas('funds', static function(EloquentBuilder $builder) {
                $builder->whereIn('funds.id', Implementation::activeFundsQuery()->select('id'));
            });
        }

        if ($has_reservations && $request->isAuthenticated()) {
            $query->whereHas('products.product_reservations', function(EloquentBuilder $builder) use ($request) {
                $builder->whereHas('voucher', function(EloquentBuilder $builder) use ($request) {
                    $builder->where('identity_address', $request->auth_address());
                });
            });
        }

        if ($has_products) {
            $query->whereHas('products', static function(EloquentBuilder $builder) use ($fund_type) {
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

        return $query->orderBy(
            $request->get('order_by', 'created_at'),
            $request->get('order_by_dir', 'asc'),
        )->latest();
    }

    /**
     * @param BaseFormRequest $request
     * @return EloquentBuilder[]|Collection
     * @noinspection PhpUnused
     */
    public static function search(BaseFormRequest $request)
    {
        return self::searchQuery($request)->get();
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function bank_connections(): HasMany
    {
        return $this->hasMany(BankConnection::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function implementations(): HasMany
    {
        return $this->hasMany(Implementation::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function bank_connection_active(): HasOne
    {
        return $this->hasOne(BankConnection::class)->where([
            'state' => BankConnection::STATE_ACTIVE,
        ]);
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
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function voucher_transaction_bulks(): HasMany
    {
        return $this->hasMany(VoucherTransactionBulk::class);
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
        return $this->belongsToMany(Fund::class, 'fund_providers');
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
        return $this->belongsToMany(Fund::class,'fund_providers')->where([
            'fund_providers.state' => FundProvider::STATE_ACCEPTED
        ])->where(function(EloquentBuilder $builder) {
            $builder->where('fund_providers.allow_budget', true);
            $builder->orWhere(function(EloquentBuilder $builder) {
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
        )->where(function(EloquentBuilder $builder) {
            $builder->where('fund_providers.state', FundProvider::STATE_ACCEPTED);
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
        )->where(static function(EloquentBuilder $builder) {
            $builder->where('fund_providers.state', FundProvider::STATE_ACCEPTED);
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
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function employees_with_trashed(): HasMany
    {
        /** @var Employee|HasMany $relation */
        $relation = $this->hasMany(Employee::class);
        $relation->withTrashed();

        return $relation;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     * @noinspection PhpUnused
     */
    public function last_employee_session(): HasManyThrough
    {
        return $this->hasOneThrough(
            Session::class,
            Employee::class,
            'organization_id',
            'identity_address',
            'id',
            'identity_address'
        )->latest('last_activity_at');
    }

    /**
     * @param string|array $role
     * @return EloquentBuilder|Relation
     */
    public function employeesOfRoleQuery($role)
    {
        return EmployeeQuery::whereHasRoleFilter($this->employees(), $role);
    }

    /**
     * @param string|array $permission
     * @return EloquentBuilder|Relation
     */
    public function employeesWithPermissionsQuery($permission)
    {
        return EmployeeQuery::whereHasPermissionFilter($this->employees(), $permission);
    }

    /**
     * @param array|int $fund_id
     * @return EloquentBuilder
     */
    public function providerProductsQuery($fund_id = []): EloquentBuilder
    {
        $productsQuery = ProductQuery::whereNotExpired($this->products()->getQuery());
        $productsQuery = ProductQuery::whereFundNotExcludedOrHasHistory($productsQuery, $fund_id);

        return $productsQuery->whereNull('sponsor_organization_id');
    }

    /**
     * @param string|array $permission
     * @return Collection|Employee[]
     */
    public function employeesWithPermissions($permission): Collection
    {
        return $this->employeesWithPermissionsQuery($permission)->get();
    }

    /**
     * @param Identity $identity
     * @return EloquentBuilder
     */
    public function identityPermissionsQuery(Identity $identity): EloquentBuilder
    {
        return Permission::whereRelation('roles.employees', [
            'organization_id' => $this->id,
            'identity_address' => $identity->address,
        ]);
    }

    /**
     * Returns identity organization permissions
     * @param ?Identity $identity
     * @return Collection
     */
    public function identityPermissions(?Identity $identity): SupportCollection
    {
        if ($identity && strcmp($identity->address, $this->identity_address) === 0) {
            return Permission::allMemCached();
        }

        return $identity ? $this->identityPermissionsQuery($identity)->get() : collect();
    }

    /**
     * Check if identity is organization employee
     * @param Identity $identity
     * @return bool
     */
    public function isEmployee(Identity $identity): bool
    {
        return $this->employees()->where('identity_address', $identity->address)->exists();
    }

    /**
     * @param Identity|null $identity
     * @param array|string $permissions
     * @param bool $all
     * @return bool
     */
    public function identityCan(
        Identity $identity = null,
        array|string  $permissions = [],
        bool $all = true
    ): bool {
        // convert string to array
        $permissions = (array) $permissions;

        if (!$identity || !$identity->exists || !$identity->address || count($permissions) === 0) {
            return false;
        }

        // as owner of the organization you don't have any restrictions
        if ($identity->address === $this->identity_address) {
            return true;
        }

        // retrieving the list of all the permissions that the identity has
        $permissionKeys = $this->identityPermissions($identity)->pluck('key');
        $permissionsCount = $permissionKeys->intersect($permissions)->count();

        return $all ? $permissionsCount === count($permissions) : $permissionsCount > 0;
    }

    /**
     * @param $identityAddress string
     * @param string|array|bool $permissions
     * @return EloquentBuilder
     */
    public static function queryByIdentityPermissions(
        string $identityAddress,
        string|array|bool $permissions = false
    ): EloquentBuilder {
        $permissions = $permissions === false ? false : (array) $permissions;

        /**
         * Query all the organizations where identity_address has permissions
         * or is the creator
         */
        return self::query()->where(static function(EloquentBuilder $builder) use (
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
     * @return Employee|Model|null
     */
    public function findEmployee(string $identity_address): Employee|Model|null
    {
        return $this->employees()->where(compact('identity_address'))->first();
    }

    /**
     * @param $fund_id
     * @return Fund|Model|null
     */
    public function findFund($fund_id = null): Fund|Model|null
    {
        return $this->funds()->where('funds.id', $fund_id)->first();
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return EloquentBuilder
     */
    public static function searchProviderOrganizations(
        Organization $sponsor,
        array $options
    ): EloquentBuilder {
        $postcodes = array_get($options, 'postcodes');
        $providerIds = array_get($options, 'provider_ids');

        /** @var Carbon|null $dateFrom */
        $dateFrom = array_get($options, 'date_from');
        /** @var Carbon|null $dateTo */
        $dateTo = array_get($options, 'date_to');

        $query = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $sponsor);

        if ($providerIds) {
            $query->whereIn('id', $providerIds);
        }

        if ($postcodes) {
            $query->whereHas('offices', static function(EloquentBuilder $builder) use ($postcodes) {
                $builder->whereIn('postcode_number', (array) $postcodes);
            });
        }

        if ($dateFrom && $dateTo) {
            $query->whereHas('voucher_transactions', function(EloquentBuilder $builder) use ($dateFrom, $dateTo) {
                $builder->where('created_at', '>=', $dateFrom->clone()->startOfDay());
                $builder->where('created_at', '<=', $dateTo->clone()->endOfDay());
            });
        }

        $queryTransactions = (new FinancialStatisticQueries())->getFilterTransactionsQuery($sponsor, $options);
        $queryTransactions->whereColumn('organization_id', 'organizations.id');

        $query->addSelect([
            'total_spent' => (clone $queryTransactions)->selectRaw('sum(`amount`)'),
            'highest_transaction' => (clone $queryTransactions)->selectRaw('max(`amount`)'),
            'nr_transactions' => (clone $queryTransactions)->selectRaw('count(`id`)'),
        ])->orderByDesc('total_spent');

        return $query;
    }

    /**
     * @return array
     */
    public function productsMeta(): array
    {
        return [
            'total_archived' => Product::onlyTrashed()->where('organization_id', $this->id)->count(),
            'total_provider' => $this->products()->whereNull('sponsor_organization_id')->count(),
            'total_sponsor' => $this->products()->whereNotNull('sponsor_organization_id')->count(),
        ];
    }

    /**
     * @param Bank $bank
     * @param Employee $employee
     * @param Implementation $implementation
     * @return BankConnection|\Illuminate\Database\Eloquent\Model
     */
    public function makeBankConnection(
        Bank $bank,
        Employee $employee,
        Implementation $implementation
    ): BankConnection {
        return BankConnection::addConnection($bank, $employee, $this, $implementation);
    }

    /**
     * @return void
     */
    public function updateFundBalancesByBankConnection(): void
    {
        /** @var Fund[] $funds */
        $balanceProvider = Fund::BALANCE_PROVIDER_BANK_CONNECTION;
        $funds = FundQuery::whereTopUpAndBalanceUpdateAvailable($this->funds(), $balanceProvider)->get();
        $balance = $funds->isNotEmpty() ? $this->bank_connection_active->fetchBalance() : null;

        if ($funds->isNotEmpty() && $balance) {
            foreach ($funds as $fund) {
                $fund->setBalance($balance->getAmount(), $this->bank_connection_active);
            }
        }
    }

    /**
     * @param Identity $identity
     * @param array $roles
     * @return Employee
     */
    public function addEmployee(Identity $identity, array $roles = []): Employee
    {
        /** @var Employee $employee */
        $employee = $this->employees()->firstOrCreate([
            'identity_address' => $identity->address,
        ]);

        $employee->roles()->sync($roles);
        EmployeeCreated::dispatch($employee);

        return $employee;
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function isOwner(Identity $identity): bool
    {
        return $this->identity_address == $identity->address;
    }
}
