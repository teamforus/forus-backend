<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundProviderChatResource;
use App\Models\FundProviderChat;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderChatQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundProviderChatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Product $product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request,
        Organization $organization,
        Product $product
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        $this->authorize('viewAnyProvider', [
            FundProviderChat::class, $product, $organization,
        ]);

        $query = FundProviderChatQuery::whereProductAndProviderOrganizationFilter(
            FundProviderChat::query(),
            $product->id,
            $organization->id
        );

        return FundProviderChatResource::queryCollection($query, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundProviderChatResource
     */
    public function show(
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat
    ): FundProviderChatResource {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        $this->authorize('viewProvider', [
            $fundProviderChat, $product, $organization,
        ]);

        return FundProviderChatResource::create($fundProviderChat);
    }
}
