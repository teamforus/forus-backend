<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductResource;
use App\Models\Fund;
use App\Models\FundProviderChatMessage;
use Illuminate\Database\Eloquent\Builder;

class ProviderProductResource extends ProductResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            ...parent::toArray($request),
            'sponsor_organization_id' => $this->resource->sponsor_organization_id,
            'sponsor_organization' => new OrganizationBasicResource($this->resource->sponsor_organization),
            'unseen_messages' => $this->hasUnseenMessages(),
            'excluded_funds' => Fund::whereHas('providers.product_exclusions', function(Builder $builder) {
                $builder->where('product_id', '=', $this->resource->id);
                $builder->orWhereNull('product_id');
            })->select([
                'id', 'name', 'state'
            ])->get(),
            ...$this->extraPaymentConfigs(),
        ];
    }

    /**
     * @return bool
     */
    protected function hasUnseenMessages(): bool
    {
        return FundProviderChatMessage::whereIn(
            'fund_provider_chat_id', $this->resource->fund_provider_chats()->pluck('id')
        )->where('provider_seen', '=', false)->count();
    }

    /**
     * @return array
     */
    private function extraPaymentConfigs(): array
    {
        return $this->resource->organization->canReceiveExtraPayments()
            ? $this->resource->only('reservation_extra_payments')
            : [];
    }
}
