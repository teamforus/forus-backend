<?php

namespace App\Models;

use App\Events\FundProviders\FundProviderStateUpdated;
use App\Events\Products\ProductApproved;
use App\Events\Products\ProductRevoked;
use App\Scopes\Builders\FundProviderChatQuery;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read Collection|\App\Models\FundProviderProduct[] $fund_provider_products_with_trashed
 * @property-read int|null $fund_provider_products_with_trashed_count
 * @property-read string $state_locale
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization $organization
 * @property-read Collection|\App\Models\FundProviderProductExclusion[] $product_exclusions
 * @property-read int|null $product_exclusions_count
 * @property-read Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static Builder|FundProvider newModelQuery()
 * @method static Builder|FundProvider newQuery()
 * @method static Builder|FundProvider query()
 * @method static Builder|FundProvider whereAllowBudget($value)
 * @method static Builder|FundProvider whereAllowProducts($value)
 * @method static Builder|FundProvider whereAllowSomeProducts($value)
 * @method static Builder|FundProvider whereCreatedAt($value)
 * @method static Builder|FundProvider whereFundId($value)
 * @method static Builder|FundProvider whereId($value)
 * @method static Builder|FundProvider whereOrganizationId($value)
 * @method static Builder|FundProvider whereState($value)
 * @method static Builder|FundProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProvider extends Model
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
        'allow_products', 'allow_budget', 'allow_some_products',
    ];

    /**
     * @var array
     */
    protected $casts = [
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
        return $this->organization->getLastActivity();
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
    private static function exportTransform(Builder $builder) {
        $transKey = "export.providers";

        return $builder->with([
            'organization'
        ])->get()->map(function(FundProvider $fundProvider) use ($transKey) {
            $provider = $fundProvider->organization;
            $lastActivity = $provider->getLastActivity();

            $provider_products_count = ProductQuery::whereNotExpired(
                $provider->products_provider()->getQuery()
            )->count();
            
            $sponsor_products_count = ProductQuery::whereNotExpired($provider->products_sponsor()->where([
                'sponsor_organization_id' => $fundProvider->fund->organization_id
            ])->getQuery())->count();

            $active_products_count = ProductQuery::approvedForFundsAndActiveFilter(
                $fundProvider->products()->getQuery(),
                $fundProvider->fund_id
            )->count();

            $hasIndividualProducts = $fundProvider->fund_provider_products()->whereHas('product')->exists();

            return [
                trans("$transKey.fund") => $fundProvider->fund->name,
                trans("$transKey.fund_type") => $fundProvider->fund->type,
                trans("$transKey.provider") => $provider->name,
                trans("$transKey.iban") => $provider->iban,
                trans("$transKey.provider_last_activity") => $lastActivity ? $lastActivity->diffForHumans(now()) : null,
                trans("$transKey.products_provider_count") => $provider_products_count,
                trans("$transKey.products_sponsor_count") => $sponsor_products_count,
                trans("$transKey.products_active_count") => $active_products_count,
                trans("$transKey.products_count") => $provider_products_count + $sponsor_products_count,
                trans("$transKey.phone") => $provider->phone,
                trans("$transKey.email") => $provider->email,
                trans("$transKey.phone") => $provider->phone,
                trans("$transKey.kvk") => $fundProvider->organization->kvk,
                trans("$transKey.state") => $fundProvider->state_locale,
                trans("$transKey.allow_budget") => $fundProvider->allow_budget ? 'Ja' : 'Nee',
                trans("$transKey.allow_products") => $fundProvider->allow_products ? 'Ja' : 'Nee',
                trans("$transKey.allow_some_products") => $hasIndividualProducts || $fundProvider->allow_products ? 'Ja' : 'Nee',
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
    ) {
        return self::exportTransform(self::search($request, $organization, $builder));
    }

    /**
     * @param array $products
     * @return $this
     */
    public function approveProducts(array $products): self
    {
        $productIds = array_pluck($products, 'id');
        $isTypeSubsidy = $this->fund->isTypeSubsidy();

        $oldProducts = $this->products()->pluck('products.id')->toArray();
        $newProducts = array_diff($productIds, $oldProducts);

        foreach ($products as $product) {
            $productModel = Product::findOrFail($product['id']);
            $product['price'] = $productModel->price;

            if ($product['limit_total_unlimited'] ?? false) {
                $product['limit_total'] = 0;
                $product['limit_total_unlimited'] = 1;
            }

            $this->fund_provider_products()->firstOrCreate([
                'product_id' => $product['id'],
            ])->update($isTypeSubsidy ? array_only($product, [
                'limit_total', 'limit_total_unlimited', 'limit_per_identity',
                'expire_at', 'amount', 'price',
            ]) : []);
        }

        $newProducts = Product::whereIn('products.id', $newProducts)->get();
        $newProducts->each(function(Product $product) {
            ProductApproved::dispatch($product, $this->fund);
        });

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
     * @param array $products
     * @return $this
     */
    public function declineProducts(array $products): self
    {
        $oldProducts = $this->products()->pluck('products.id')->toArray();
        $detachedProducts = array_intersect($oldProducts, $products);

        $this->fund_provider_products()->whereHas('product', static function(
            Builder $builder
        ) use ($products) {
            $builder->whereIn('products.id', $products);
        })->delete();

        $detachedProducts = Product::whereIn('products.id', $detachedProducts)->get();
        $detachedProducts->each(function(Product $product) {
            ProductRevoked::dispatch($product, $this->fund);
        });

        FundProviderChatQuery::whereProductFilter(
            $this->fund_provider_chats()->getQuery(),
            $products
        )->get()->each(static function(FundProviderChat $chat) {
            $chat->addSystemMessage('Aanbieding afgewezen.', auth_address());
        });

        return $this;
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

        if ($state === self::STATE_ACCEPTED && $this->isPending() && $this->fund->isTypeBudget()) {
            $this->update([
                'allow_budget' => true,
                'allow_products' => true,
            ]);
        }

        $approvedBefore = $this->isApproved();
        $this->update(compact('state'));
        $approvedAfter = $this->isApproved();

        FundProviderStateUpdated::dispatch($this, compact([
            'originalState', 'approvedBefore', 'approvedAfter',
        ]));
    }
}
