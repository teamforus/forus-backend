<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\OfficeResource;
use App\Http\Controllers\Controller;
use App\Models\Office;

class OfficesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return OfficeResource::collection(Office::all());
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
