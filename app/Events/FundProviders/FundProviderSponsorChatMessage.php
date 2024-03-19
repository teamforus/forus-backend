<?php

namespace App\Events\FundProviders;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;

class FundProviderSponsorChatMessage extends BaseFundProviderEvent
{
    protected $message;

    /**
     * @return FundProviderChat
     */
    public function getChat(): FundProviderChat
    {
        return $this->message->fund_provider_chat;
    }
}
