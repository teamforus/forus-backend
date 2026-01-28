<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OfficeResource;
use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderProductExclusion;
use Illuminate\Http\Request;

class ProviderProductResource extends ProductResource
{
    public const array LOAD = [
        'voucher_transactions',
        'product_reservations_pending',
        'organization.fund_providers_allowed_extra_payments',
        'organization.fund_providers_allowed_extra_payments_full',
        'organization.mollie_connection',
        'organization.reservation_fields',
        'organization.fund_providers.fund',
        'organization.fund_providers.product_exclusions',
        'bookmarks',
        'reservation_fields',
        'fund_provider_chats.messages',
    ];

    public const array LOAD_NESTED = [
        'photos' => MediaResource::class,
        'product_category' => ProductCategoryResource::class,
        'organization' => OrganizationBasicResource::class,
        'organization.offices' => OfficeResource::class,
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
            ...$this->getProductData($request, $this->resource, false),
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
                ->map(fn (FundProvider $fundProvider) => $fundProvider->fund?->only([
                    'id', 'name', 'state',
                ]))
                ->filter()
                ->values(),
            ...$this->resource->only([
                'sku', 'ean',
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
}
