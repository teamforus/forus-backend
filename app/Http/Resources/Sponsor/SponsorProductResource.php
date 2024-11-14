<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\Tiny\FundTinyResource;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\FundQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property int $sponsor_id
 * @property Product $resource
 * @property bool|null $with_monitored_history
 * @property Organization|null $sponsor_organization
 */
class SponsorProductResource extends BaseJsonResource
{
    public const array LOAD = [
        'photo.presets',
        'product_reservations_pending',
        'product_category.translations',
        'organization.logo.presets',
        'organization.business_type.translations',
        'sponsor_organization.logo.presets',
        'sponsor_organization.business_type.translations',
        'product_exclusions',
        'logs_last_monitored_field_changed',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var FundProvider $fundProvider */
        $fundProvider = $request->route('fund_provider');
        $product = $this->resource;
        $funds = $this->getProductFunds($product, $this->sponsor_organization);

        return [
            ...$product->only([
                'id', 'name', 'description', 'product_category_id', 'sold_out',
                'organization_id', 'price_type', 'price_type_discount', 'sponsor', 'sponsor_organization_id',
                'reservation_enabled', 'reservation_policy', 'alternative_text', 'ean',
            ]),
            'description_html' => $product->description_html,
            'organization' => new OrganizationBasicResource($product->organization),
            'sponsor_organization' => new OrganizationBasicResource($this->resource->sponsor_organization),
            'total_amount' => $product->total_amount,
            'unlimited_stock' => $product->unlimited_stock,
            'reserved_amount' => $product->countReservedCached($fundProvider?->fund),
            'sold_amount' => $product->countSold($fundProvider?->fund),
            'stock_amount' => $product->stock_amount,
            'price' => currency_format($product->price),
            'price_locale' => $product->price_locale,
            'price_type' => $product->price_type,
            'price_discount' => $product->price_discount ? currency_format($product->price_discount) : null,
            'expired' => $product->expired,
            'deleted' => !is_null($product->deleted_at),
            'photo' => new MediaResource($product->photo),
            'product_category' => new ProductCategoryResource($product->product_category),
            'is_available' => $this->isAvailable($product, $fundProvider) ,
            'deals_history' => $fundProvider ? $this->getDealsHistory($product, $fundProvider) : null,
            'funds' => FundTinyResource::collection($funds ?: []),
            'monitored_changes_count' => $product->logs_monitored_field_changed()->count(),
            'monitored_history' => $this->with_monitored_history ? $this->getMonitoredHistory($product) : null,
            ...$this->productReservationFieldSettings($product),
            ...$this->makeTimestamps($product->only([
                'expire_at', 'deleted_at',
            ]), true),
            ...$this->makeTimestamps([
                'created_at' => $product->created_at,
                'last_monitored_changed_at' => $product->logs_last_monitored_field_changed?->created_at,
            ])
        ];
    }

    /**
     * @param Product $product
     * @return array|null
     */
    protected function getMonitoredHistory(Product $product): ?array
    {
        return $product->logs_monitored_field_changed->map(fn (EventLog $log) => [
            ...$log->only([
                'id',
            ]),
            ...$this->makeTimestamps($log->only([
                'created_at',
            ]))
        ])->toArray();
    }

    /**
     * @param Product $product
     * @param Organization|null $sponsor
     * @return Collection|null
     */
    private function getProductFunds(Product $product, ?Organization $sponsor): ?Collection
    {
        if (!$sponsor) {
            return null;
        }

        return FundQuery::whereProductsAreApprovedAndActiveFilter($sponsor->funds(), $product)->get();
    }

    /**
     * @param Product $product
     * @param FundProvider|null $fundProvider
     * @return bool
     */
    protected function isAvailable(Product $product, ?FundProvider $fundProvider = null): bool
    {
        return !$fundProvider || $product->product_exclusions
            ->where('fund_provider_id', $fundProvider->id)
            ->isEmpty();
    }

    /**
     * @param Product $product
     * @param FundProvider|null $fundProvider
     * @return array
     */
    protected function getDealsHistory(Product $product, ?FundProvider $fundProvider = null): array
    {
        $dealsHistoryQuery = $fundProvider->fund_provider_products()
            ->where('product_id', $product->id)
            ->withCount([
                'voucher_transactions',
                'product_reservations_pending',
            ])
            ->withTrashed()
            ->latest();

        if (!$fundProvider->fund->isTypeSubsidy()) {
            FundProviderProductQuery::whereConfigured($dealsHistoryQuery);
        }

        return $dealsHistoryQuery->get()->map(fn (FundProviderProduct $product) => array_merge($product->only([
            'id', 'amount', 'limit_total', 'limit_total_unlimited', 'limit_per_identity',
            'voucher_transactions_count', 'product_reservations_pending_count', 'active', 'product_id',
        ]), [
            'expire_at' => $product->expire_at?->format('Y-m-d'),
            'amount_locale' => currency_format_locale($product->amount),
            'expire_at_locale' => format_date_locale($product->expire_at),
        ]))->toArray();
    }

    /**
     * @param Product $product
     * @return array
     */
    private function productReservationFieldSettings(Product $product): array
    {
        return [
            'reservation_phone' => $product->reservation_phone,
            'reservation_address' => $product->reservation_address,
            'reservation_birth_date' => $product->reservation_birth_date,
        ];
    }
}