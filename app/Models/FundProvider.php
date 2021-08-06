<?php

namespace App\Models;

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
 * @property bool $dismissed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read Collection|\App\Models\FundProviderProduct[] $fund_provider_products_with_trashed
 * @property-read int|null $fund_provider_products_with_trashed_count
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
 * @method static Builder|FundProvider whereDismissed($value)
 * @method static Builder|FundProvider whereFundId($value)
 * @method static Builder|FundProvider whereId($value)
 * @method static Builder|FundProvider whereOrganizationId($value)
 * @method static Builder|FundProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProvider extends Model
{
    use HasLogs, SoftDeletes;

    public const EVENT_APPROVED_BUDGET = 'approved_budget';
    public const EVENT_REVOKED_BUDGET = 'revoked_budget';
    public const EVENT_APPROVED_PRODUCTS = 'approved_products';
    public const EVENT_REVOKED_PRODUCTS = 'revoked_products';
    public const EVENT_SPONSOR_MESSAGE = 'sponsor_message';
    public const EVENT_FUND_EXPIRING = 'fund_expiring';
    public const EVENT_FUND_STARTED = 'fund_started';
    public const EVENT_FUND_ENDED = 'fund_ended';

    public const STATE_APPROVED = 'approved';
    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED_OR_HAS_TRANSACTIONS = 'approved_or_has_transactions';

    public const STATES = [
        self::STATE_APPROVED,
        self::STATE_PENDING,
        self::STATE_APPROVED_OR_HAS_TRANSACTIONS,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'fund_id', 'dismissed',
        'allow_products', 'allow_budget', 'allow_some_products'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'dismissed' => 'boolean',
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
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function product_exclusions(): HasMany {
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
     * @param Request $request
     * @param Fund $fund
     * @return array
     */
    public function getFinances(
        Request $request,
        Fund $fund
    ): array {
        $dates = collect();

        $type = $request->input('type', 'all');
        $year = $request->input('year');
        $nth = $request->input('nth', 1);
        $product_category_id = $request->input('product_category');

        if ($type === 'quarter') {
            $startDate = Carbon::createFromDate($year, ($nth * 3) - 2, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfQuarter()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addMonth());
            $dates->push($startDate->copy()->addMonth()->addDays(14));
            $dates->push($startDate->copy()->addMonths(2));
            $dates->push($startDate->copy()->addMonths(2)->addDays(14));
            $dates->push($endDate);
        } elseif ($type === 'month') {
            $startDate = Carbon::createFromDate($year, $nth, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(4));
            $dates->push($startDate->copy()->addDays(9));
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addDays(19));
            $dates->push($startDate->copy()->addDays(24));
            $dates->push($endDate);
        } elseif ($type === 'week') {
            $startDate = Carbon::now()->setISODate(
                $year, $nth
            )->startOfWeek()->startOfDay();
            $endDate = $startDate->copy()->endOfWeek()->endOfDay();

            $dates = range_between_dates($startDate, $endDate);
        } elseif ($type === 'year') {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addQuarter());
            $dates->push($startDate->copy()->addQuarters(2));
            $dates->push($startDate->copy()->addQuarters(3));
            $dates->push($endDate);
        } elseif ($type === 'all') {
            $firstTransaction = $fund->voucher_transactions()->where([
                'organization_id' => $this->organization_id
            ])->orderBy(
                'created_at'
            )->first();

            $startDate = $firstTransaction ? $firstTransaction->created_at->subDay() : Carbon::now();
            $endDate = Carbon::now();

            $dates = range_between_dates($startDate, $endDate, 8);
        } else {
            abort(403);
            exit();
        }

        $dates = $dates->map(function (Carbon $date, $key) use ($fund, $dates, $product_category_id) {
            if ($key > 0) {
                $voucherQuery = $fund->voucher_transactions()->whereBetween(
                    'voucher_transactions.created_at', [
                        $dates[$key - 1]->copy()->endOfDay(),
                        $date->copy()->endOfDay()
                    ]
                )->where([
                    'organization_id' => $this->organization_id
                ]);

                if ($product_category_id) {
                    if ($product_category_id === -1) {
                        $voucherQuery = $voucherQuery->whereNull('voucher_transactions.product_id');
                    } else {
                        $voucherQuery = $voucherQuery->whereHas('product', function (Builder $query) use ($product_category_id) {
                            return $query->where('product_category_id', $product_category_id);
                        });
                    }
                }

                return [
                    "key" => $date->format('Y-m-d'),
                    "value" => $voucherQuery->sum('voucher_transactions.amount')
                ];
            }

            return [
                "key" => $date->format('Y-m-d'),
                "value" => 0
            ];
        });

        if ($type === 'year') {
            $dates->shift();
        }

        $transactions = $fund->voucher_transactions()->where([
            'organization_id' => $this->organization_id
        ])->whereBetween('voucher_transactions.created_at', [
            $startDate->copy()->endOfDay(),
            $endDate->copy()->endOfDay()
        ]);

        if ($product_category_id) {
            if ($product_category_id === -1) {
                $transactions->whereNull('voucher_transactions.product_id');
            } else {
                $transactions->whereHas('product', function (
                    Builder $query
                ) use ($product_category_id) {
                    return $query->where('product_category_id', $product_category_id);
                });
            }
        }

        $fundUsageInRange = $fund->voucher_transactions()->whereBetween(
            'voucher_transactions.created_at', [
                $startDate->copy()->endOfDay(),
                $endDate->copy()->endOfDay()
            ]
        );

        if ($product_category_id) {
            if ($product_category_id === -1) {
                $fundUsageInRange = $fundUsageInRange->whereNull('voucher_transactions.product_id');
            } else{
                $fundUsageInRange = $fundUsageInRange->whereHas('product', function (
                    Builder $query
                ) use($product_category_id){
                    return $query->where('product_category_id', $product_category_id);
                });
            }
        }

        $fundUsageInRange = $fundUsageInRange->sum('voucher_transactions.amount');
        $fundUsageTotal = $fund->voucher_transactions();

        if ($product_category_id) {
            if ($product_category_id === -1) {
                $fundUsageTotal = $fundUsageTotal->whereNull('voucher_transactions.product_id');
            } else {
                $fundUsageTotal = $fundUsageTotal->whereHas('product', function (
                    Builder $query
                ) use ($product_category_id) {
                    return $query->where('product_category_id', $product_category_id);
                });
            }
        }

        $fundUsageTotal = $fundUsageTotal->sum('voucher_transactions.amount');

        $providerUsageInRange = $dates->sum('value');
        $providerUsageTotal = $fund->voucher_transactions()->where([
            'organization_id' => $this->organization_id
        ]);

        if ($product_category_id) {
            if ($product_category_id === -1) {
                $providerUsageTotal = $providerUsageTotal->whereNull('voucher_transactions.product_id');
            } else {
                $providerUsageTotal = $providerUsageTotal->whereHas('product', function (
                    Builder $query
                ) use ($product_category_id) {
                    return $query->where('product_category_id', $product_category_id);
                });
            }
        }

        $providerUsageTotal = $providerUsageTotal->sum('voucher_transactions.amount');
        $avgTransaction = $transactions->average('voucher_transactions.amount');

        return [
            'dates' => $dates,
            'usage' => $providerUsageInRange,
            'transactions' => $transactions->count(),
            'avg_transaction' => round($avgTransaction ?: 0, 2),
            'share_in_range' => round($fundUsageInRange > 0 ? $providerUsageInRange / $fundUsageInRange : 0, 2),
            'share_total' => round($fundUsageTotal > 0 ? $providerUsageTotal / $fundUsageTotal : 0, 2),
        ];
    }

    /**
     * @param $providerOrganizations
     * @return array
     */
    public static function getFundProviders($providerOrganizations) : array {
        $fundProviders = collect([]);

        $providerOrganizations->each(function (Organization $organization) use (&$fundProviders) {
            foreach ($organization->fund_providers as $fund_provider) {
                if (!$fundProviders->contains('id', $fund_provider->id)) {
                    $fundProviders->push($fund_provider);
                }
            }
        });

        return $fundProviders->toArray();
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
        $fund_id = $request->input('fund_id');
        $fund_ids = $request->input('fund_ids');
        $organization_id = $request->input('organization_id');
        $dismissed = $request->input('dismissed');
        $allow_products = $request->input('allow_products');
        $allow_budget = $request->input('allow_budget');

        $query = $query ?: self::query();
        $query = $query->whereIn('fund_id', $organization->funds()->pluck('id'));

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

        if ($fund_id) {
            $query->where('fund_id', $fund_id);
        }

        if ($fund_ids) {
            $query->whereIn('fund_id', $fund_ids);
        }

        if ($organization_id) {
            $query->where('organization_id', $organization_id);
        }

        if ($dismissed !== null) {
            $query->where('dismissed', (bool) $dismissed);
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

        return $query->orderBy('created_at')->orderBy('dismissed');
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

            $provider_products_count = $provider->products_provider()->count();
            $sponsor_products_count = $provider->products_sponsor()->where([
                'sponsor_organization_id' => $fundProvider->fund->organization_id
            ])->count();

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
                trans("$transKey.allow_budget") => $fundProvider->allow_budget ? 'Ja' : 'Nee',
                trans("$transKey.allow_products") => $fundProvider->allow_products ? 'Ja' : 'Nee',
                trans("$transKey.allow_some_products") => $hasIndividualProducts || $fundProvider->allow_products ? 'Ja' : 'Nee',
            ];
        })->values();
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
}
