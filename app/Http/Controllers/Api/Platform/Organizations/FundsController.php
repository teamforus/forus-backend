<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Resources\FundResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Services\Forus\Record\Models\RecordType;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return string
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('index', Fund::class);

        return FundResource::collection($organization->funds);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequest $request
     * @param Organization $organization
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('store', Fund::class);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        /** @var Fund $fund */
        $fund = $organization->funds()->create($request->only([
            'name', 'state', 'start_date', 'end_date'
        ]));

        $fund->product_categories()->sync(
            $request->input('product_categories', [])
        );

        $fund->criteria()->create([
            'record_type_key' => 'kindpakket_2018_eligible',
            'value' => 1,
            'operator' => '='
        ]);

        if ($media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
        }

        return new FundResource($fund);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $fund);

        return new FundResource($fund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $fund);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $fund->update($request->only([
            'name', 'state', 'start_date', 'end_date'
        ]));

        $fund->product_categories()->sync(
            $request->input('product_categories', [])
        );

        if ($media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
        }

        return new FundResource($fund);
    }
}
