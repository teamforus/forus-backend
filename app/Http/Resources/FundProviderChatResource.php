<?php

namespace App\Http\Resources;

use App\Models\FundProviderChat;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FundProviderChatResource
 * @property FundProviderChat $resource
 * @package App\Http\Resources
 */
class FundProviderChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fund_provider_chat = $this->resource;

        return array_merge($fund_provider_chat->only([
            'id', 'product_id', 'fund_provider_id', 'identity_address',
        ]), [
            'fund_id' => $fund_provider_chat->fund_provider->fund_id,
            'created_at' => $fund_provider_chat->created_at->format('Y-m-d'),
            'updated_at' => $fund_provider_chat->updated_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale(
                $fund_provider_chat->created_at
            ),
            'updated_at_locale' => format_date_locale(
                $fund_provider_chat->updated_at
            ),
            'provider_unseen_messages' => $fund_provider_chat->messages()->where([
                'provider_seen' => false
            ])->count(),
            'sponsor_unseen_messages' => $fund_provider_chat->messages()->where([
                'sponsor_seen' => false
            ])->count(),
        ]);
    }
}
