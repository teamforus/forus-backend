<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\OfficeResource;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Http\Requests\Api\Platform\SearchOfficesRequest;

class OfficesController extends Controller
{
    /**
     * Display a listing of all available offices.
     *
     * @param SearchOfficesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        SearchOfficesRequest $request
    ) {
        return OfficeResource::collection(Office::search($request)->with(
            OfficeResource::$load
        )->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Office $office
     * @return OfficeResource
     */
    public function show(Office $office)
    {
        return new OfficeResource($office);
    }
}
