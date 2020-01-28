<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundProviderProduct[] $fund_provider_products
 * @property-read int|null $fund_provider_products_count
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
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
 */
class FundProvider extends Model
{
    const STATE_APPROVED = 'approved';
    const STATE_PENDING = 'pending';

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
                trans("$transKey.email") => $organization->email_public ? $organization->email : '',
                trans("$transKey.phone") => $organization->phone || '',
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
}
