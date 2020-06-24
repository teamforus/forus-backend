<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\FundProviders;

use App\Events\FundProviders\FundProviderSponsorChatMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats\IndexFundProviderChatRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats\StoreFundProviderChatRequest;
use App\Http\Resources\FundProviderChatResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\Organization;
use App\Models\Product;

class FundProviderChatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderChatRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderChatRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ) {
        $this->authorize('showSponsor', [
            $fundProvider, $organization, $fund
        ]);

        $this->authorize('viewAnySponsor', [
            FundProviderChat::class, $fundProvider, $fund, $organization
        ]);

        $query = $fundProvider->fund_provider_chats();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        return FundProviderChatResource::collection(
            $query->paginate($request->input('per_page'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundProviderChatRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @return FundProviderChatResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundProviderChatRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ) {
        $this->authorize('showSponsor', [
            $fundProvider, $organization, $fund
        ]);

        $this->authorize('createSponsor', [
            FundProviderChat::class, $fundProvider, $fund, $organization
        ]);

        $chatMessage = $fundProvider->startChat(
            Product::find($request->input('product_id')),
            $request->input('message'),
            auth_address()
        );

        FundProviderSponsorChatMessage::dispatch($chatMessage);

        return new FundProviderChatResource($chatMessage->fund_provider_chat);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat $fundProviderChat
     * @return FundProviderChatResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        FundProviderChat $fundProviderChat
    ) {
        $this->authorize('showSponsor', [
            $fundProvider, $organization, $fund
        ]);

        $this->authorize('viewSponsor', [
            $fundProviderChat, $fundProvider, $fund, $organization
        ]);

        return new FundProviderChatResource($fundProviderChat);
    }
}
