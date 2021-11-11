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
     * Create a new event instance.
     *
     * FundProviderReplied constructor.
     * @param Fund $fund
     * @param FundProviderChatMessage $message
     */
    public function __construct(Fund $fund, FundProviderChatMessage $message)
    {
        parent::__construct($fund);
        $this->message = $message;
    }

    /**
     * @return FundProviderChat
     */
    public function getChat(): FundProviderChat
    {
        return $this->message->fund_provider_chat;
    }
}
