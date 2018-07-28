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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index() {
        $this->authorize('index', Organization::class);

        return OrganizationResource::collection(Organization::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOrganizationRequest $request
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreOrganizationRequest $request
    ) {
        $this->authorize('store', Organization::class);

        $organization = Organization::create(
            collect($request->only([
                'name', 'iban', 'email', 'phone', 'kvk', 'btw'
            ]))->merge([
                'identity_address' => $request->get('identity'),
            ])->toArray()
        );

        $organization->product_categories()->sync(
            $request->input('product_categories', [])
        );

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
        $this->authorize('show', Organization::class);

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

        $organization->update($request->only([
            'name', 'iban', 'email', 'phone', 'kvk', 'btw'
        ]));

        $organization->product_categories()->sync(
            $request->input('product_categories', [])
        );

        return new OrganizationResource($organization);
    }
}
