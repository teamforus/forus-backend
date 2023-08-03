<?php

namespace App\Models;

use App\Events\FundProviders\FundProviderStateUpdated;
use App\Events\Products\ProductApproved;
use App\Events\Products\ProductRevoked;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

/**
 * App\Models\FundProvider
 *
 * @property int $id
 * @property int $organization_id
 * @property int $fund_id
 * @property bool $allow_budget
 * @property bool $allow_products
 * @property bool $allow_some_products
 * @property bool $excluded
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read Collection<int, \App\Models\FundProviderChat> $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read Collection<int, \App\Models\FundProviderProduct> $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read Collection<int, \App\Models\FundProviderProduct> $fund_provider_products_with_trashed
 * @property-read int|null $fund_provider_products_with_trashed_count
 * @property-read Collection<int, \App\Models\FundProviderUnsubscribe> $fund_unsubscribes
 * @property-read int|null $fund_unsubscribes_count
 * @property-read Collection|\App\Models\FundProviderUnsubscribe[] $fund_unsubscribes_active
 * @property-read int|null $fund_unsubscribes_active_count
 * @property-read string $state_locale
 * @property-read Collection<int, \App\Services\EventLogService\Models\EventLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization $organization
 * @property-read Collection<int, \App\Models\FundProviderProductExclusion> $product_exclusions
 * @property-read int|null $product_exclusions_count
 * @property-read Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static Builder|FundProvider newModelQuery()
 * @method static Builder|FundProvider newQuery()
 * @method static Builder|FundProvider query()
 * @method static Builder|FundProvider whereAllowBudget($value)
 * @method static Builder|FundProvider whereAllowProducts($value)
 * @method static Builder|FundProvider whereAllowSomeProducts($value)
 * @method static Builder|FundProvider whereCreatedAt($value)
 * @method static Builder|FundProvider whereExcluded($value)
 * @method static Builder|FundProvider whereFundId($value)
 * @method static Builder|FundProvider whereId($value)
 * @method static Builder|FundProvider whereOrganizationId($value)
 * @method static Builder|FundProvider whereState($value)
 * @method static Builder|FundProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProvider extends BaseModel
{
    use HasLogs;

    public const EVENT_BUNQ_TRANSACTION_SUCCESS = 'bunq_transaction_success';
    public const EVENT_STATE_ACCEPTED = 'state_accepted';
    public const EVENT_STATE_REJECTED = 'state_rejected';
    public const EVENT_APPROVED_BUDGET = 'approved_budget';
    public const EVENT_REVOKED_BUDGET = 'revoked_budget';
    public const EVENT_APPROVED_PRODUCTS = 'approved_products';
    public const EVENT_REVOKED_PRODUCTS = 'revoked_products';
    public const EVENT_SPONSOR_MESSAGE = 'sponsor_message';
    public const EVENT_FUND_EXPIRING = 'fund_expiring';
    public const EVENT_FUND_STARTED = 'fund_started';
    public const EVENT_FUND_ENDED = 'fund_ended';

    public const STATE_PENDING = 'pending';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_REJECTED = 'rejected';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_ACCEPTED,
        self::STATE_REJECTED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'fund_id', 'state',
        'allow_products', 'allow_budget', 'allow_some_products', 'excluded',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'excluded' => 'boolean',
        'allow_budget' => 'boolean',
        'allow_products' => 'boolean',
        'allow_some_products' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @noinspection PhpUnused
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'fund_provider_products');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_provider_products(): HasMany
    {
        return $this->hasMany(FundProviderProduct::class);
    }

    /**
     * @param Organization $organization
     * @return array
     */
    public static function makeTotalsMeta(Organization $organization): array
    {
        return [
            'active' => static::queryActive($organization)->count(),
            'pending' => static::queryPending($organization)->count(),
            'archived' => static::queryArchived($organization)->count(),
            'available' => static::queryAvailableFunds($organization)->count(),
            'invitations' => static::queryInvitationsActive($organization)->count(),
            'unsubscriptions' => static::queryUnsubscriptions($organization)->count(),
            'invitations_archived' => static::queryInvitationsArchived($organization)->count(),
        ];
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation
     */
    public static function queryPending(Organization $organization): Builder|Relation
    {
        return $organization
            ->fund_providers()
            ->whereNotIn('id', self::queryActive($organization)->select('id'))
            ->whereNotIn('id', self::queryArchived($organization)->select('id'));
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation
     */
    public static function queryArchived(Organization $organization): Builder|Relation
    {
        return $organization
            ->fund_providers()
            ->whereHas('fund', fn (Builder $q) => FundQuery::whereExpired($q));
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation|FundProvider
     */
    public static function queryActive(Organization $organization): Builder|Relation|FundProvider
    {
        return $organization->fund_providers()
            ->where('state', FundProvider::STATE_ACCEPTED)
            ->whereNotIn('id', static::queryArchived($organization)->select('id'));
    }

    /**
     * @param Organization $organization
     * @return Builder
     */
    public static function queryAvailableFunds(Organization $organization): Builder
    {
        $query = Implementation::queryFundsByState(Fund::STATE_ACTIVE, Fund::STATE_PAUSED);
        $query->where('type', '!=', Fund::TYPE_EXTERNAL);
        $query->whereNotIn('id', $organization->fund_providers()->pluck('fund_id'));

        FundQuery::whereIsInternal($query);
        FundQuery::whereIsConfiguredByForus($query);

        return $query;
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation
     */
    public static function queryInvitationsActive(Organization $organization): Builder|Relation
    {
        $expireTime = now()->subMinutes(FundProviderInvitation::VALIDITY_IN_MINUTES);
        $query = $organization->fund_provider_invitations();

        return $query
            ->where('created_at', '>', $expireTime)
            ->where('state', '=', FundProviderInvitation::STATE_PENDING)
            ->whereRelation('fund', 'archived', false);
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation
     */
    public static function queryInvitationsArchived(Organization $organization): Builder|Relation
    {
        return $organization
            ->fund_provider_invitations()
            ->whereNotIn('id', self::queryInvitationsActive($organization)->select('id'));
    }

    /**
     * @param Organization $organization
     * @return Builder|Relation
     */
    public static function queryUnsubscriptions(Organization $organization): Builder|Relation
    {
        return FundProviderUnsubscribe::whereHas('fund_provider', fn (Builder $q) => $q->where([
            'organization_id' => $organization->id,
        ]));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_provider_products_with_trashed(): HasMany
    {
        /** @var HasMany|SoftDeletes $hasMany */
        $hasMany = $this->hasMany(FundProviderProduct::class);

        return $hasMany->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function fund_provider_chats(): HasMany
    {
        return $this->hasMany(FundProviderChat::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function product_exclusions(): HasMany
    {
        return $this->hasMany(FundProviderProductExclusion::class);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getLastActivity(): ?Carbon
    {
        return $this->organization->last_employee_session?->last_activity_at;
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_unsubscribes() : HasMany
    {
        return $this->hasMany(FundProviderUnsubscribe::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_unsubscribes_active() : HasMany
    {
        return $this->hasMany(FundProviderUnsubscribe::class)->where([
            'canceled' => false,
        ]);
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state == self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->state == self::STATE_ACCEPTED;
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->state == self::STATE_REJECTED;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param Builder|null $query
     * @return Builder
     */
    public static function search(
        Request $request,
        Organization $organization,
        Builder $query = null
    ): Builder {
        $allow_products = $request->input('allow_products');
        $allow_budget = $request->input('allow_budget');
        $has_products = $request->input('has_products');

        $fundsQuery = $organization->funds()->where('archived', false);
        $query = $query ?: self::query();
        $query = $query->whereIn('fund_id', $fundsQuery->select('id')->getQuery());

        if ($q = $request->input('q')) {
            $query->where(static function(Builder $builder) use ($q) {
                $builder->whereHas('organization', function (Builder $query) use ($q) {
                    $query->where('name', 'like', "%$q%");
                    $query->orWhere('kvk', 'like', "%$q%");
                    $query->orWhere('email', 'like', "%$q%");
                    $query->orWhere('phone', 'like', "%$q%");
                });

                $builder->orWhereHas('fund', function (Builder $query) use ($q) {
                    $query->where('name', 'like', "%$q%");
                });
            });
        }

        if ($request->has('fund_id')) {
            $query->where('fund_id', $request->input('fund_id'));
        }

        if ($request->has('fund_ids')) {
            $query->whereIn('fund_id', $request->input('fund_ids'));
        }

        if ($request->has('state')) {
            $query->where('state', $request->input('state'));
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($allow_budget !== null) {
            $query->whereHas('fund', function(Builder $builder) {
                $builder->where('type', Fund::TYPE_BUDGET);
            })->where('allow_budget', (bool) $allow_budget);
        }

        if ($has_products) {
            $query->whereHas('organization.products', function (Builder $builder) use ($fundsQuery) {
                ProductQuery::whereNotExpired($builder);
                ProductQuery::whereFundNotExcluded($builder, $fundsQuery->pluck('id')->toArray());
            });
        }

        if (!$has_products && $has_products !== null) {
            $query->whereDoesntHave('organization.products', function (Builder $builder) use ($fundsQuery) {
                ProductQuery::whereNotExpired($builder);
                ProductQuery::whereFundNotExcluded($builder, $fundsQuery->pluck('id')->toArray());
            });
        }

        if ($allow_products !== null) {
            if ($allow_products === 'some') {
                $query->where(function(Builder $builder) use ($allow_products) {
                    $builder->where(function(Builder $builder) use ($allow_products) {
                        $builder->whereHas('fund', function(Builder $builder) {
                            $builder->where('type', Fund::TYPE_BUDGET);
                        })->whereHas('products');
                    });

                    $builder->orWhere(function(Builder $builder) use ($allow_products) {
                        $builder->whereHas('fund', function(Builder $builder) {
                            $builder->where('type', Fund::TYPE_SUBSIDIES);
                        })->whereHas('fund_provider_products.product');
                    });
                });
            } else {
                $query->where(function(Builder $builder) use ($allow_products) {
                    $builder->where(function(Builder $builder) use ($allow_products) {
                        $builder->whereHas('fund', function(Builder $builder) {
                            $builder->where('type', Fund::TYPE_BUDGET);
                        })->where('allow_products', (bool) $allow_products);
                    });

                    $builder->orWhere(function(Builder $builder) use ($allow_products) {
                        $builder->whereHas('fund', function(Builder $builder) {
                            $builder->where('type', Fund::TYPE_SUBSIDIES);
                        });

                        if ($allow_products) {
                            $builder->whereHas('fund_provider_products.product');
                        } else {
                            $builder->whereDoesntHave('fund_provider_products.product');
                        }
                    });
                });
            }
        }

        return $query->orderBy('created_at');
    }

    /**
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder): mixed
    {
        return $builder->with([
            'fund',
            'organization.last_employee_session',
        ])->get()->map(function(FundProvider $fundProvider) {
            $transKey = "export.providers";
            $provider = $fundProvider->organization;
            $lastActivity = $fundProvider->getLastActivity();

            $providerProductsQuery = ProductQuery::whereNotExpired($provider->products_provider());
            $individualProductsQuery = $fundProvider->fund_provider_products()->whereHas('product');

            $sponsorProductsQuery = ProductQuery::whereNotExpired($provider->products_sponsor()->where([
                'sponsor_organization_id' => $fundProvider->fund->organization_id,
            ]));

            $activeProductsQuery = ProductQuery::approvedForFundsAndActiveFilter(
                $fundProvider->products()->getQuery(),
                $fundProvider->fund_id,
            );

            $result = DB::query()->select([
                'individual_products_count' => $individualProductsQuery->selectRaw('count(*)'),
                'provider_products_count' => $providerProductsQuery->selectRaw('count(*)'),
                'sponsor_products_count' => $sponsorProductsQuery->selectRaw('count(*)'),
                'active_products_count' => $activeProductsQuery->selectRaw('count(*)'),
            ])->first();

            $hasIndividualProducts = ($result->individual_products_count > 0 || $fundProvider->allow_products);

            return [
                trans("$transKey.fund") => $fundProvider->fund->name,
                trans("$transKey.fund_type") => $fundProvider->fund->type,
                trans("$transKey.provider") => $provider->name,
                trans("$transKey.iban") => $provider->iban,
                trans("$transKey.provider_last_activity") => $lastActivity?->diffForHumans(now()),
                trans("$transKey.products_provider_count") => $result->provider_products_count,
                trans("$transKey.products_sponsor_count") => $result->sponsor_products_count,
                trans("$transKey.products_active_count") => $result->active_products_count,
                trans("$transKey.products_count") => $result->provider_products_count + $result->sponsor_products_count,
                trans("$transKey.phone") => $provider->phone,
                trans("$transKey.email") => $provider->email,
                trans("$transKey.phone") => $provider->phone,
                trans("$transKey.kvk") => $fundProvider->organization->kvk,
                trans("$transKey.state") => $fundProvider->state_locale,
                trans("$transKey.allow_budget") => $fundProvider->allow_budget ? 'Ja' : 'Nee',
                trans("$transKey.allow_products") => $fundProvider->allow_products ? 'Ja' : 'Nee',
                trans("$transKey.allow_some_products") => $hasIndividualProducts ? 'Ja' : 'Nee',
            ];
        })->values();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return trans('states/fund_providers.' . $this->state);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param Builder|null $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function export(
        Request $request,
        Organization $organization,
        ?Builder $builder = null
    ): mixed {
        return self::exportTransform(self::search($request, $organization, $builder));
    }

    /**
     * @param array $products
     * @return $this
     */
    public function approveProducts(array $products): self
    {
        $productIds = array_pluck($products, 'id');
        $oldProducts = $this->products()->pluck('products.id')->toArray();
        $newProducts = array_diff($productIds, $oldProducts);

        foreach ($products as $product) {
            if ($this->fund->isTypeBudget()) {
                $this->approveProduct($this->prepareProductApproveData($product));
            } else {
                $this->approveSubsidyProduct($this->prepareProductApproveData($product));
            }
        }

        foreach (Product::whereIn('products.id', $newProducts)->get() as $product) {
            Event::dispatch(new ProductApproved($product, $this->fund));
        }

        return $this;
    }

    /**
     * @param array $productData
     * @return array
     */
    protected function prepareProductApproveData(array $productData): array
    {
        $isTypeSubsidy = $this->fund->isTypeSubsidy();

        if (is_null($productData['limit_total'] ?? null) && $isTypeSubsidy) {
            $productData['limit_total'] = 1;
        }

        if (is_null($productData['limit_per_identity'] ?? null) && $isTypeSubsidy) {
            $productData['limit_per_identity'] = 1;
        }

        if ($productData['limit_total_unlimited'] ?? false) {
            $productData['limit_total'] = null;
            $productData['limit_total_unlimited'] = 1;
        }

        return array_merge(array_only($productData, [
            'id', 'limit_total', 'limit_total_unlimited', 'limit_per_identity', 'expire_at', 'amount',
        ]), $isTypeSubsidy ? [
            'price' => Product::findOrFail($productData['id'])->price,
        ] : []);
    }

    /**
     * @param array $data
     * @param bool $withTrashed
     * @return FundProviderProduct
     */
    protected function findFundProviderProductByIdOrCreate(array $data, bool $withTrashed = false): FundProviderProduct
    {
        $query = $this->fund_provider_products()
            ->latest('created_at')
            ->latest('id');

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->firstOrCreate([
            'product_id' => $data['id'],
        ]);
    }

    /**
     * @param array $data
     * @return FundProviderProduct
     */
    protected function approveSubsidyProduct(array $data): FundProviderProduct
    {
        return $this->findFundProviderProductByIdOrCreate($data)->updateModel($data);
    }

    /**
     * @param array $data
     * @return FundProviderProduct
     */
    protected function approveProduct(array $data): FundProviderProduct
    {
        $fundProviderProduct = $this->findFundProviderProductByIdOrCreate($data, true);

        if ($fundProviderProduct->trashed()) {
            $fundProviderProduct->restore();
        }

        $hasConfigs =
            !is_null(Arr::get($data, 'expire_at')) ||
            !is_null(Arr::get($data, 'limit_total')) ||
            !is_null(Arr::get($data, 'limit_per_identity')) ||
            !is_null(Arr::get($data, 'limit_total_unlimited'));

        $hasChanged =
            (Arr::get($data, 'expire_at') != $fundProviderProduct->expire_at?->format('Y-m-d')) ||
            (Arr::get($data, 'limit_total') != $fundProviderProduct->limit_total) ||
            (Arr::get($data, 'limit_per_identity') != $fundProviderProduct->limit_per_identity) ||
            (((bool) Arr::get($data, 'limit_total_unlimited')) != $fundProviderProduct->limit_total_unlimited);

        if ($hasChanged) {
            $fundProviderProduct->delete();
            $fundProviderProduct = $this->findFundProviderProductByIdOrCreate($data);
        }

        return $fundProviderProduct->updateModel(array_merge($hasConfigs ? $data : []));
    }

    /**
     * @param array $products
     * @return $this
     */
    public function resetProducts(array $products): self
    {
        foreach ($products as $product) {
            $this->resetProduct($product);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return FundProviderProduct|null
     */
    protected function resetProduct(array $data): ?FundProviderProduct
    {
        $query = $this->fund_provider_products()->latest();

        if ($this->fund->isTypeBudget()) {
            $fundProviderProducts = (clone $query)->where('product_id', $data['id'])->latest()->get();
            $fundProviderProducts->each(fn(FundProviderProduct $product) => $product->delete());

            return $query->firstOrCreate([
                'product_id' => $data['id'],
            ]);
        }

        return null;
    }

    /**
     * @param array $products
     * @return $this
     */
    public function declineProducts(array $products): self
    {
        $attachedProducts = $this->products()->pluck('products.id')->toArray();
        $products = Product::whereIn('id', array_intersect($products, $attachedProducts))->get();

        $this->fund_provider_products()->whereIn('product_id', $products->pluck('id'))->delete();
        $chats = $this->fund_provider_chats()->whereIn('product_id', $products->pluck('id'))->get();

        foreach ($products as $product) {
            Event::dispatch(new ProductRevoked($product, $this->fund));
        }

        foreach ($chats as $chat) {
            $chat->addSystemMessage('Aanbieding afgewezen.', auth()->id());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return FundProviderQuery::whereApprovedForFundsFilter(
            self::query()->where('id', $this->id), $this->fund_id
        )->exists();
    }

    /**
     * @return bool
     */
    public function hasTransactions(): bool {
        return FundProviderQuery::whereHasTransactions(
            self::query()->where('id', $this->id), $this->fund_id
        )->exists();
    }

    /**
     * @param Product $product
     * @param string $message
     * @param string $identity_address
     * @return FundProviderChatMessage
     */
    public function startChat(
        Product $product,
        string $message,
        string $identity_address
    ): FundProviderChatMessage {
        /** @var FundProviderChat $chat */
        $chat = $this->fund_provider_chats()->create([
            'product_id' => $product->id,
            'identity_address' => $identity_address,
        ]);

        return $chat->addSponsorMessage($message, $identity_address);
    }

    /**
     * @param string $state
     * @return void
     */
    public function setState(string $state): void
    {
        $originalState = $this->state;

        $approvedBefore = $this->isApproved();
        $this->update(compact('state'));
        $approvedAfter = $this->isApproved();

        FundProviderStateUpdated::dispatch($this, compact([
            'originalState', 'approvedBefore', 'approvedAfter',
        ]));
    }

    /**
     * @return bool
     */
    public function canUnsubscribe(): bool
    {
        return
            $this->isAccepted() &&
            $this->fund_unsubscribes->where(fn (
                FundProviderUnsubscribe $unsubscribe
            ) => $unsubscribe->isPending() && !$unsubscribe->canceled)->isEmpty();
    }
}
