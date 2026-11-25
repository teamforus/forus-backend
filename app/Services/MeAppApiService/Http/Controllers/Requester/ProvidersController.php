<?php

namespace App\Services\MeAppApiService\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Services\MeAppApiService\Http\Requests\Requester\IndexProvidersRequest;
use App\Services\MeAppApiService\Http\Resources\Requester\ProviderResource;
use App\Services\MeAppApiService\Searches\ProviderSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProvidersController extends Controller
{
    /**
     * List providers for product filtering.
     *
     * @param IndexProvidersRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexProvidersRequest $request): AnonymousResourceCollection
    {
        $search = new ProviderSearch($request->only(['q']));

        return ProviderResource::queryCollection($search->query(), $request)->preserveQuery();
    }
}
