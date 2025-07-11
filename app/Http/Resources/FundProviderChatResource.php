<?php

namespace App\Http\Resources;

use App\Models\FundProviderChat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FundProviderChat $resource
 */
class FundProviderChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $chat = $this->resource;

        return [
            ...$chat->only([
                'id', 'product_id', 'fund_provider_id', 'identity_address',
            ]),
            'fund_id' => $chat->fund_provider->fund_id,
            'created_at' => $chat->created_at->format('Y-m-d'),
            'updated_at' => $chat->updated_at->format('Y-m-d'),
            'created_at_locale' => format_date_locale($chat->created_at),
            'updated_at_locale' => format_date_locale($chat->updated_at),
            'provider_unseen_messages' => $chat->messages()->where('provider_seen', false)->count(),
            'sponsor_unseen_messages' => $chat->messages()->where('sponsor_seen', false)->count(),
        ];
    }
}
