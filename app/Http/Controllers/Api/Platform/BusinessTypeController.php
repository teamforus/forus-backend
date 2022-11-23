<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchBusinessTypesRequest;
use App\Http\Resources\BusinessTypeResource;
use App\Models\BusinessType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Searches\BusinessTypeSearch;

class BusinessTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SearchBusinessTypesRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(SearchBusinessTypesRequest $request): AnonymousResourceCollection
    {
        $search = new BusinessTypeSearch($request->only('used', 'parent_id', 'per_page'));
        return BusinessTypeResource::queryCollection($search->query(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param BusinessType $businessType
     * @return BusinessTypeResource
     */
    public function show(BusinessType $businessType): BusinessTypeResource
    {
        return BusinessTypeResource::create($businessType);
    }
}
