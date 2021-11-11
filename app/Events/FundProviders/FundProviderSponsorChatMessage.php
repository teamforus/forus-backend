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
     * Create a new event instance.
     *
     * FundProviderRevokedProducts constructor.
     * @param FundProvider $fundProvider
     * @param FundProviderChatMessage $message
     */
    public function __construct(FundProvider $fundProvider, FundProviderChatMessage $message)
    {
        parent::__construct($fundProvider);
        $this->message = $message;
    }

    /**
     * @return FundProviderChat
     */
    public function getChat(): FundProviderChat
    {
        return $this->message->fund_provider_chat;
    }

    /**
     * @return Fund
     */
    public function getFund(): Fund
    {
        return $this->message->fund_provider_chat->fund_provider->fund;
    }
}
