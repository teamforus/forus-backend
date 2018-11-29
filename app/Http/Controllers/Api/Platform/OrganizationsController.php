<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class OrganizationsController extends Controller
{
    /**
     * Display a listing of all identity organizations.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index() {
        $this->authorize('index', Organization::class);

        $identityAddress = auth()->user()->getAuthIdentifier();

        return OrganizationResource::collection(
            Organization::queryByIdentityPermissions(
                $identityAddress
            )->get()
        );
    }

    /**
     * Store a newly created identity organization in storage.
     *
     * @param StoreOrganizationRequest $request
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreOrganizationRequest $request
    ) {
        $this->authorize('store', Organization::class);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $organization = Organization::create(
            collect($request->only([
                'name', 'iban', 'email', 'phone', 'kvk', 'btw'
            ]))->merge([
                'identity_address' => auth()->user()->getAuthIdentifier(),
            ])->toArray()
        );

        $organization->product_categories()->sync(
            $request->input('product_categories', [])
        );

        if ($media && $media->type == 'organization_logo') {
            $organization->attachMedia($media);
        }

        try {
            $offices = collect(app()->make('kvk_api')->getOffices(
                $request->input('kvk')
            ));

            foreach (collect($offices ?: null) as $office) {
                $organization->offices()->create(
                    collect($office)->only([
                        'address', 'lon', 'lat'
                    ])->toArray()
                );
            }
        } catch (\Exception $e) { }

        return new OrganizationResource($organization);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization
    ) {
        $this->authorize('show', $organization);

        return new OrganizationResource($organization);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOrganizationRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $organization->update($request->only([
            'name', 'iban', 'email', 'phone', 'kvk', 'btw'
        ]));

        $organization->product_categories()->sync(
            $request->input('product_categories', [])
        );

        if ($media && $media->type == 'organization_logo') {
            $organization->attachMedia($media);
        }

        return new OrganizationResource($organization);
    }
}
