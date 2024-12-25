<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\SearchOfficesRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Searches\OfficeSearch;
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
        $search = new OfficeSearch($request->only([
            'q', 'approved',
        ]), Office::query());

        return OfficeResource::queryCollection($search->query(), $request);
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
