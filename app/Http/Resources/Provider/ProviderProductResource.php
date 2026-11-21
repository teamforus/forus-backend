<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReservationFieldResource;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderProductExclusion;
use App\Models\Product;
use Illuminate\Http\Request;

class ProviderProductResource extends ProductResource
{
    public const array LOAD = [
        'voucher_transactions_sold',
        'product_reservations_pending',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'organization.reservation_fields',
        'organization.fund_providers.fund',
        'organization.fund_providers.product_exclusions',
        'reservation_fields',
        'fund_provider_chats.messages',
    ];

    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
        'product_category' => ProductCategoryResource::class,
        'organization' => OrganizationBasicResource::class,
        'sponsor_organization' => OrganizationBasicResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'total_amount' => $this->resource->total_amount,
            'sold_amount' => $this->resource->countSoldCached(),
            'stock_amount' => $this->resource->stock_amount,
            'unlimited_stock' => $this->resource->unlimited_stock,
            'reserved_amount' => $this->resource->countReservedCached(),
            'price_discount' => $this->resource->price_discount
                ? currency_format($this->resource->price_discount)
                : null,
            'photos' => MediaResource::collection($this->resource->photos),
            ...$this->providerReservationSettings($this->resource),
            'sponsor_organization_id' => $this->resource->sponsor_organization_id,
            'sponsor_organization' => new OrganizationBasicResource($this->resource->sponsor_organization),
            'unseen_messages' => $this->hasUnseenMessages(),
            'excluded_funds' => $this->resource->organization->fund_providers
                ->filter(function (FundProvider $fundProvider) {
                    return $fundProvider->product_exclusions->contains(
                        fn (FundProviderProductExclusion $exclusion) => (
                            $exclusion->product_id === null ||
                            $exclusion->product_id === $this->resource->id
                        )
                    );
                })
                ->map(fn (FundProvider $fundProvider) => $fundProvider->fund?->only(['id']))
                ->filter()
                ->values(),
            ...$this->resource->only([
                'sku', 'ean', 'description', 'product_category_id', ...self::SCANNING_AND_RESERVATION_FIELDS,
            ]),
            ...$this->extraPaymentConfigs(),
        ];
    }

    /**
     * @return int
     */
    protected function hasUnseenMessages(): int
    {
        return $this->resource->fund_provider_chats->sum(
            fn (FundProviderChat $chat) => $chat->messages->where('provider_seen', false)->count()
        );
    }

    /**
     * @return array
     */
    private function extraPaymentConfigs(): array
    {
        return $this->resource->only([
            'reservation_extra_payments',
        ]);
    }

    /**
     * @param Product $product
     * @return array
     */
    private function providerReservationSettings(Product $product): array
    {
        return [
            ...$product->only([
                'reservation_phone', 'reservation_fields_enabled', 'reservation_address', 'reservation_birth_date',
                'reservation_note', 'reservation_note_text', 'reservation_fields_config',
            ]),
            'reservation_fields' => ReservationFieldResource::collection($product->reservation_fields),
            'available_reservation_fields_for_provider' => ReservationFieldResource::collection(
                $product->getAvailableReservationFieldsForProvider(),
            ),
        ];
    }
}
