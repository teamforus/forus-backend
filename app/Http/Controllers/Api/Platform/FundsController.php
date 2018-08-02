<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\FundResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Fund::class);

        return FundResource::collection(Fund::all());
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Fund $fund)
    {
        $this->authorize('show', $fund);

        return new FundResource($fund);
    }
}
