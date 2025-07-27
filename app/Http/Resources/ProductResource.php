<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property Product $resource
 */
class ProductResource extends BaseJsonResource
{
    public const LOAD = [
        'voucher_transactions',
        'product_reservations_pending',
        'photo.presets',
        'product_category.translations',
        'organization.offices.photo.presets',
        'organization.offices.schedules',
        'organization.offices.organization',
        'organization.offices.organization.logo.presets',
        'organization.logo.presets',
        'organization.business_type.translations',
        'organization.fund_providers',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'bookmarks',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $product = $this->resource;

        return array_merge($this->baseFields($product), [
            'photo' => new MediaResource($product->photo),
            'organization' => new OrganizationBasicResource($product->organization),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached(),
            'sold_amount' => $product->countSold(),
            'stock_amount' => $product->stock_amount,
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'expire_at_locale' => format_date_locale($product->expire_at ?? null),
            'expired' => $product->expired,
            'deleted_at' => $product->deleted_at?->format('Y-m-d'),
            'deleted_at_locale' => format_date_locale($product->deleted_at ?? null),
            'deleted' => $product->trashed(),
            'funds' => $product->trashed() ? [] : $this->getProductFunds($baseRequest, $product),
            'offices' => OfficeResource::collection($product->organization->offices),
            'product_category' => new ProductCategoryResource($product->product_category),
            'bookmarked' => $product->isBookmarkedBy($baseRequest->identity()),
        ], array_merge(
            $this->priceFields($product),
            $this->productReservationFieldSettings($product),
        ));
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function baseFields(Product $product): array
    {
        return [
            ...$product->only([
                'id', 'name', 'description', 'product_category_id', 'sold_out',
                'organization_id', 'reservation_enabled', 'reservation_policy', 'alternative_text',
                'description_html',
            ]),
            ...$product->translateColumns(
                $this->isCollection()
                    ? $product->only(['name'])
                    : $product->only(['name', 'description_html']),
            ),
            'price' => is_null($product->price) ? null : currency_format($product->price),
            'price_locale' => $product->price_locale,
            'organization' => $product->organization->only('id', 'name'),
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function priceFields(Product $product): array
    {
        $price_min = $this->getProductSubsidyPrice($product, 'min');
        $price_max = $this->getProductSubsidyPrice($product, 'max');
        $lowest_price = min($product->price, $price_min);

        return [
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'price_discount_locale' => $product->price_discount_locale,
            'price_min' => currency_format($price_min),
            'price_min_locale' => currency_format_locale($price_min),
            'price_max' => currency_format($price_max),
            'price_max_locale' => currency_format_locale($price_max),
            'lowest_price' => $product->price_type === 'regular' ? currency_format($lowest_price) : null,
            'lowest_price_locale' => $product->price_type === 'regular' ? currency_format_locale($lowest_price) : null,
        ];
    }

    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Fund::query();
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @return Collection
     */
    private function getProductFunds(BaseFormRequest $request, Product $product): Collection
    {
        $fundsQuery = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);
        $fundsQuery->with([
            'organization', 'logo',
        ]);

        return $fundsQuery->get()->map(function (Fund $fund) use ($product, $request) {
            $fundProviderProduct = $product->getFundProviderProduct($fund);

            $reservationsEnabled = $product->reservationsEnabled();
            $reservationsExtraPaymentEnabled = $reservationsEnabled && $product->reservationExtraPaymentsEnabled($fund);

            $scanningEnabled = $product->organization->fund_providers
                ->where('allow_budget', true)
                ->where('fund_id', $fund->id)
                ->isNotEmpty() && (!$fundProviderProduct || $fundProviderProduct->allow_scanning);

            $data = [
                'id' => $fund->id,
                ...$fund->translateColumns($fund->only(['name'])),
                'logo' => new MediaResource($fund->logo),
                'organization' => $fund->organization->only([
                    'id', 'name',
                ]),
                'end_at' => $fund->end_date?->format('Y-m-d'),
                'end_at_locale' => format_date_locale($fund->end_date ?? null),
                'feature_reservations_enabled' => $reservationsEnabled,
                'feature_reservation_extra_payments_enabled' => $reservationsExtraPaymentEnabled,
                'feature_scanning_enabled' => $scanningEnabled,
            ];

            $productData = ProductSubQuery::appendReservationStats([
                'identity_id' => $request->auth_id(),
                'fund_id' => $fund->id,
            ], Product::whereId($product->id))->firstOrFail()->only([
                'limit_total', 'limit_per_identity', 'limit_available',
            ]);

            $fundProviderProduct = $product->getFundProviderProduct($fund);

            return [
                ...$data,
                ...$productData,
                ...$fundProviderProduct?->isPaymentTypeSubsidy() ? [
                    'user_price' => $fundProviderProduct->user_price,
                    'user_price_locale' => $fundProviderProduct->user_price_locale,
                ] : [
                    'user_price' => $product->price,
                    'user_price_locale' => $product->price_locale,
                ],
                'limit_per_identity' => $fundProviderProduct?->limit_per_identity,
            ];
        })->values();
    }

    /**
     * @param Product $product
     * @param string $type
     * @return float
     */
    private function getProductSubsidyPrice(Product $product, string $type): float
    {
        return max($product->price - $product->fund_provider_products()->where([
            'product_id' => $product->id,
        ])->whereHas('fund_provider.fund', function (Builder $builder) {
            $builder->whereIn('funds.id', $this->fundsQuery()->select('funds.id'));
        })->$type('amount'), 0);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function productReservationFieldSettings(Product $product): array
    {
        $global = $product::RESERVATION_FIELD_GLOBAL;
        $request = BaseFormRequest::createFromBase(request());
        $organization = $product->organization;
        $fields = $organization->reservation_fields;

        if ($request->isWebshop()) {
            return [
                'reservation' => $product->reservation_fields ? [
                    'phone' => $product->reservation_phone === $global ?
                        $organization->reservation_phone :
                        $product->reservation_phone,
                    'address' => $product->reservation_address === $global ?
                        $organization->reservation_address :
                        $product->reservation_address,
                    'birth_date' => $product->reservation_birth_date === $global ?
                        $organization->reservation_birth_date :
                        $product->reservation_birth_date,
                    'note' => $organization->reservation_user_note,
                    'fields' => OrganizationReservationFieldResource::collection($fields),
                ] : [
                    'phone' => $product::RESERVATION_FIELD_NO,
                    'address' => $product::RESERVATION_FIELD_NO,
                    'birth_date' => $product::RESERVATION_FIELD_NO,
                    'note' => $organization->reservation_user_note,
                    'fields' => [],
                ],
            ];
        }

        return [
            'reservation_phone' => $product->reservation_phone,
            'reservation_fields' => $product->reservation_fields,
            'reservation_address' => $product->reservation_address,
            'reservation_birth_date' => $product->reservation_birth_date,
        ];
    }
}
