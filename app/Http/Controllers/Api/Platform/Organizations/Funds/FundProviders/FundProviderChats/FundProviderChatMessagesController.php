<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\FundProviders\FundProviderChats;

use App\Events\FundProviders\FundProviderSponsorChatMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats\IndexFundProviderChatMessageRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats\StoreFundProviderChatMessageRequest;
use App\Http\Resources\FundProviderChatMessageResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundProviderChatMessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderChatMessageRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat $fundProviderChat
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderChatMessageRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        FundProviderChat $fundProviderChat
    ): AnonymousResourceCollection {
        $this->authorize('showSponsor', [
            $fundProvider, $organization, $fund
        ]);

        $this->authorize('viewAnySponsor', [
            FundProviderChat::class, $fundProvider, $fund, $organization
        ]);

        $this->authorize('viewAnySponsor', [
            FundProviderChatMessage::class, $fundProviderChat, $fundProvider, $fund, $organization
        ]);

        $fundProviderChat->messages()->update([
            'sponsor_seen' => true
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
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat $fundProviderChat
     * @return FundProviderChatMessageResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundProviderChatMessageRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        FundProviderChat $fundProviderChat
    ): FundProviderChatMessageResource {
        $this->authorize('showSponsor', [$fundProvider, $organization, $fund]);
        $this->authorize('viewSponsor', [$fundProviderChat, $fundProvider, $fund, $organization]);

        $this->authorize('createSponsor', [
            FundProviderChatMessage::class, $fundProviderChat, $fundProvider, $fund, $organization
        ]);

        $chatMessage = $fundProviderChat->addSponsorMessage(
            $request->input('message'),
            $request->auth_address()
        );

        FundProviderSponsorChatMessage::dispatch($fundProvider, $chatMessage);

        return new FundProviderChatMessageResource($chatMessage);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat $fundProviderChat
     * @param FundProviderChatMessage $fundProviderChatMessage
     * @return FundProviderChatMessageResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        FundProviderChat $fundProviderChat,
        FundProviderChatMessage $fundProviderChatMessage
    ): FundProviderChatMessageResource {
        $this->authorize('showSponsor', [
            $fundProvider, $organization, $fund
        ]);

        $this->authorize('viewSponsor', [
            $fundProviderChat, $fundProvider, $fund, $organization
        ]);

        $this->authorize('viewSponsor', [
            $fundProviderChatMessage, $fundProviderChat, $fundProvider, $fund, $organization
        ]);

        $fundProviderChatMessage->update([
            'sponsor_seen' => true
        ]);

        return new FundProviderChatMessageResource(
            $fundProviderChat->messages()->find($fundProviderChatMessage->id)
        );
    }
}
