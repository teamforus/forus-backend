<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchBusinessTypesRequest;
use App\Http\Resources\BusinessTypeResource;
use App\Models\BusinessType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SearchBusinessTypesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(SearchBusinessTypesRequest $request): AnonymousResourceCollection
    {
        return BusinessTypeResource::queryCollection(BusinessType::search($request), $request);
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
