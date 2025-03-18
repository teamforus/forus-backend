<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products\FundProviderChats;

use App\Events\Funds\FundProviderChatMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats\StoreFundProviderChatMessageRequest;
use App\Http\Resources\FundProviderChatMessageResource;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundProviderChatMessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request,
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        $this->authorize('viewAnyProvider', [
            FundProviderChatMessage::class, $fundProviderChat, $product, $organization,
        ]);

        $fundProviderChat->messages()->update([
            'provider_seen' => true,
        ]);

        return FundProviderChatMessageResource::collection(
            $fundProviderChat->messages()->paginate($request->input('per_page'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundProviderChatMessageRequest $request
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderChatMessageResource
     */
    public function store(
        StoreFundProviderChatMessageRequest $request,
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat
    ): FundProviderChatMessageResource {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        $this->authorize('createProvider', [
            FundProviderChatMessage::class, $fundProviderChat, $product, $organization,
        ]);

        $chatMessage = $fundProviderChat->addProviderMessage(
            $request->input('message'),
            $request->auth_address()
        );

        FundProviderChatMessageEvent::dispatch($fundProviderChat->fund_provider->fund, $chatMessage);

        return new FundProviderChatMessageResource($chatMessage);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @param FundProviderChatMessage $fundProviderChatMessage
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderChatMessageResource
     */
    public function show(
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat,
        FundProviderChatMessage $fundProviderChatMessage
    ): FundProviderChatMessageResource {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        $this->authorize('viewProvider', [
            $fundProviderChatMessage, $fundProviderChat, $product, $organization,
        ]);

        $fundProviderChatMessage->update([
            'provider_seen' => true,
        ]);

        return new FundProviderChatMessageResource(
            $fundProviderChat->messages()->find($fundProviderChatMessage->id)
        );
    }
}
