<?php

namespace App\Events\Funds;

use App\Models\Fund;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;

/**
 * Class FundProviderChatMessageEvent
 * @package App\Events\Funds
 */
class FundProviderChatMessageEvent extends BaseFundEvent
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
