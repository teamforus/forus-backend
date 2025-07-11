<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FundProviderChatResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @property int $sponsor_id
 * @property Product $resource
 * @property ?FundProvider $fundProvider
 * @property Collection|Fund[] $funds
 * @property bool|null $with_monitored_history
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
    protected Collection |null $funds = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $fundProvider = $this->fundProvider;
        $chat = $fundProvider ? $product->fund_provider_chats->where('fund_provider_id', $fundProvider->id)->first() : null;

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
            'is_available' => $this->isAvailable($product, $fundProvider),
            'deals_history' => $fundProvider ? $this->getDealsHistory($product, $fundProvider) : null,
            'funds' => $this->funds ? $this->getFundData($this->funds, $product) : [],
            'monitored_changes_count' => $product->logs_monitored_field_changed()->count(),
            'monitored_history' => $this->with_monitored_history ? $this->getMonitoredHistory($product) : null,
            'fund_provider_product_chat' => $chat ? new FundProviderChatResource($chat) : null,
            ...$this->productReservationFieldSettings($product),
            ...$this->makeTimestamps($product->only([
                'expire_at', 'deleted_at',
            ]), true),
            ...$this->makeTimestamps([
                'created_at' => $product->created_at,
                'last_monitored_changed_at' => $product->logs_last_monitored_field_changed?->created_at,
            ]),
        ];
    }

    /**
     * @param Collection $funds
     * @param Product|null $product
     * @return array
     */
    protected function getFundData(Collection $funds, Product $product = null): array
    {
        $approvedIds = FundQuery::whereProductsAreApprovedAndActiveFilter(
            Fund::query()->whereIn('id', $funds->pluck('id')->toArray()),
            $product,
        )->pluck('id')->toArray();

        return $funds->map(function (Fund $fund) use ($product, $approvedIds) {
            $fundProviderId = $fund->providers->where('organization_id', $product->organization_id)->first()?->id;

            return [
                ...$fund->only([
                    'id', 'type', 'type_locale', 'name', 'organization_id',
                ]),
                'implementation' => $fund->fund_config?->implementation?->only([
                    'id', 'name',
                ]),
                'fund_provider_id' => $fundProviderId,
                'state' => in_array($fund->id, $approvedIds) ? 'approved' : ($fundProviderId ? 'pending' : 'not_applied'),
                'state_locale' => in_array($fund->id, $approvedIds) ? 'Goedgekeurd' : (
                    $fundProviderId ? 'In behandeling' : 'Niet van toepassing'
                ),
                'logo' => new MediaResource($fund->logo),
                'url' => $fund->urlWebshop(),
                'url_product' => $product ? $fund->urlWebshop('/aanbod/' . $product->id) : null,
                'organization_name' => $fund->organization->name,
            ];
        })->toArray();
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
            'fields' => $this->getHistoryFields($log),
            ...$this->makeTimestamps($log->only([
                'created_at',
            ])),
        ])->toArray();
    }

    /**
     * @param EventLog $log
     * @return array
     */
    protected function getHistoryFields(EventLog $log): array
    {
        $list = collect(Arr::get($log->data, 'product_updated_fields', []));

        return $list->mapWithKeys(function ($row, $key) {
            if ($key === 'description') {
                return [
                    $key => [
                        'from' => (new Product(['description' => $row['from'] ?? '']))->description_html,
                        'to' => (new Product(['description' => $row['to'] ?? '']))->description_html,
                    ],
                ];
            }

            return [$key => $row];
        })->toArray();
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
            ->latest('created_at')
            ->latest('id');

        return $dealsHistoryQuery->get()->map(function (FundProviderProduct $productHistory) use ($product) {
            $amountIdentity = $productHistory->isPaymentTypeSubsidy()
                ? currency_format(floatval($productHistory->price) - floatval($productHistory->amount))
                : $productHistory->price;

            return [
                ...$productHistory->only([
                    'id', 'amount',
                    'limit_total', 'limit_total_unlimited', 'limit_per_identity', 'limit_per_identity_unlimited',
                    'voucher_transactions_count', 'product_reservations_pending_count', 'active', 'product_id',
                    'payment_type', 'payment_type_locale', 'allow_scanning',
                ]),
                'amount_identity' => $amountIdentity,
                'amount_identity_locale' => $amountIdentity === null ? 'Geen informatie' : currency_format_locale($amountIdentity),
                'amount_locale' => currency_format_locale($productHistory->amount),
                ...$this->makeTimestamps($productHistory->only(['expire_at']), dateOnly: true),
                ...$this->makeTimestamps($productHistory->only(['created_at', 'updated_at'])),
            ];
        })->toArray();
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
