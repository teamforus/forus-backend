<?php

namespace App\Events\FundProviders;

use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FundProviderSponsorChatMessage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $message;

    /**
     * Create a new event instance.
     *
     * FundProviderRevokedProducts constructor.
     * @param FundProviderChatMessage $message
     */
    public function __construct(FundProviderChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return FundProviderChat
     */
    public function getChat() {
        return $this->message->fund_provider_chat;
    }

    /**
     * @return \App\Models\Fund
     */
    public function getFund() {
        return $this->message->fund_provider_chat->fund_provider->fund;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
