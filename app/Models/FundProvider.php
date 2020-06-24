<?php

namespace App\Models;

use App\Events\Products\ProductApproved;
use App\Events\Products\ProductRevoked;
use App\Scopes\Builders\FundProviderChatQuery;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Collection;
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderChat[] $fund_provider_chats
 * @property-read int|null $fund_provider_chats_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereAllowBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereAllowProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereAllowSomeProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereDismissed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 */
class FundProvider extends Model
{
    use HasLogs;

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
     */
    public function products() {
        return $this->belongsToMany(Product::class, 'fund_provider_products');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_products() {
        return $this->hasMany(FundProviderProduct::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_provider_chats() {
        return $this->hasMany(FundProviderChat::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Request $request
     * @param Fund $fund
     * @return array
     */
    public function getFinances(
        Request $request,
        Fund $fund
    ) {
        $dates = collect();

        $type = $request->input('type', 'all');
        $year = $request->input('year');
        $nth = $request->input('nth', 1);
        $product_category_id = $request->input('product_category');

        if ($type == 'quarter') {
            $startDate = Carbon::createFromDate($year, ($nth * 3) - 2, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfQuarter()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addMonths(1));
            $dates->push($startDate->copy()->addMonths(1)->addDays(14));
            $dates->push($startDate->copy()->addMonths(2));
            $dates->push($startDate->copy()->addMonths(2)->addDays(14));
            $dates->push($endDate);
        } elseif ($type == 'month') {
            $startDate = Carbon::createFromDate($year, $nth, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(4));
            $dates->push($startDate->copy()->addDays(9));
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addDays(19));
            $dates->push($startDate->copy()->addDays(24));
            $dates->push($endDate);
        } elseif ($type == 'week') {
            $startDate = Carbon::now()->setISODate(
                $year, $nth
            )->startOfWeek()->startOfDay();
            $endDate = $startDate->copy()->endOfWeek()->endOfDay();

            $dates = range_between_dates($startDate, $endDate);
        } elseif ($type == 'year') {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($year, 1, 1)->endOfYear();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addMonths(4));
            $dates->push($startDate->copy()->addMonths(8));
            $dates->push($endDate);
        } elseif ($type == 'all') {
            $firstTransaction = $fund->voucher_transactions()->where([
                'organization_id' => $this->organization_id
            ])->orderBy(
                'created_at'
            )->first();

            $startDate = $firstTransaction ? $firstTransaction->created_at->subDay() : Carbon::now();
            $endDate = Carbon::now();

            $dates = range_between_dates($startDate, $endDate, 8);
        } else {
            abort(403, "");
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
                    if ($product_category_id == -1) {
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

        $transactions = $fund->voucher_transactions()->where([
            'organization_id' => $this->organization_id
        ])->whereBetween('voucher_transactions.created_at', [
            $startDate->copy()->endOfDay(),
            $endDate->copy()->endOfDay()
        ]);

        if ($product_category_id) {
            if ($product_category_id == -1) {
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
            if ($product_category_id == -1) {
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
            if ($product_category_id == -1) {
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
            if ($product_category_id == -1) {
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
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function search(
        Request $request,
        Organization $organization
    ) {
        $q = $request->input('q', null);
        $fund_id = $request->input('fund_id', null);
        $dismissed = $request->input('dismissed', null);
        $allow_products = $request->input('allow_products', null);
        $allow_budget = $request->input('allow_budget', null);

        $providers = FundProvider::query()->whereIn(
            'fund_id',
            $organization->funds()->pluck('id')
        );

        if ($q) {
            $providers = $providers->whereHas('organization', function (
                Builder $query
            ) use ($q) {
                return $query->where('name', 'like', "%{$q}%")
                    ->orWhere('kvk', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($fund_id) {
            $providers->where('fund_id', $fund_id);
        }

        if ($dismissed !== null) {
            $providers->where('dismissed', !!$dismissed);
        }

        if ($allow_budget !== null) {
            $providers->where('allow_budget', !!$allow_budget);
        }

        if ($allow_products !== null) {
            if ($allow_products === 'some') {
                $providers->whereHas('products');
            } else {
                $providers->where('allow_products', !!$allow_products);
            }
        }

        return $providers->orderBy('created_at')->orderBy('dismissed');
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
            $organization = $fundProvider->organization;

            return [
                trans("$transKey.provider") => $organization->name,
                trans("$transKey.email") => $organization->email,
                trans("$transKey.phone") => $organization->phone,
                trans("$transKey.kvk") => $fundProvider->organization->kvk,
                trans("$transKey.allow_budget") => $fundProvider->allow_budget ? 'Ja' : 'Nee',
                trans("$transKey.allow_products") => $fundProvider->allow_products ? 'Ja' : 'Nee',
                trans("$transKey.allow_some_products") => $fundProvider->allow_some_products ? 'Ja' : 'Nee',
            ];
        })->values();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function export(
        Request $request,
        Organization $organization
    ) {
        return self::exportTransform(self::search($request, $organization));
    }

    /**
     * @param array $products
     * @return $this
     */
    public function approveProducts(array $products)
    {
        $oldProducts = $this->products()->pluck('products.id')->toArray();
        $newProducts = array_diff($products, $oldProducts);

        $this->products()->attach($products);

        $newProducts = Product::whereIn('products.id', $newProducts)->get();
        $newProducts->each(function(Product $product) {
            ProductApproved::dispatch($product, $this->fund);
        });

        return $this;
    }

    /**
     * @param array $products
     * @return $this
     */
    public function declineProducts(array $products)
    {
        $oldProducts = $this->products()->pluck('products.id')->toArray();
        $detachedProducts = array_intersect($oldProducts, $products);

        $this->products()->detach($products);

        $detachedProducts = Product::whereIn('products.id', $detachedProducts)->get();
        $detachedProducts->each(function(Product $product) {
            ProductRevoked::dispatch($product, $this->fund);
        });

        FundProviderChatQuery::whereProductFilter(
            $this->fund_provider_chats()->getQuery(),
            $products
        )->get()->each(function(FundProviderChat $chat) {
            $chat->addSystemMessage('Aanbieding afgewezen.', auth_address());
        });

        return $this;
    }

    /**
     * @param Product $product
     * @param string $message
     * @param string $identity_address
     * @return mixed
     */
    public function startChat(
        Product $product,
        string $message,
        string $identity_address
    ) {
        /** @var FundProviderChat $chat */
        $chat = $this->fund_provider_chats()->create([
            'product_id' => $product->id,
            'identity_address' => $identity_address,
        ]);

        return $chat->addSponsorMessage($message, $identity_address);
    }
}
