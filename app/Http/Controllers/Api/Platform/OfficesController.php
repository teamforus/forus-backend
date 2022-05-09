<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\OfficeResource;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Http\Requests\Api\Platform\SearchOfficesRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficesController extends Controller
{
    /**
     * Display a listing of all available offices.
     *
     * @param SearchOfficesRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(SearchOfficesRequest $request): AnonymousResourceCollection
    {
        return OfficeResource::queryCollection(Office::search($request), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Office $office
     * @return OfficeResource
     */
    public function show(Office $office): OfficeResource
    {
        return OfficeResource::create($office);
    }
}
