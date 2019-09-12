<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
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

        return OrganizationResource::collection(
            Organization::queryByIdentityPermissions(auth_address())->with(
                OrganizationResource::$load
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

        if ($media = media()->findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        $organization = Organization::create(
            collect($request->only([
                'name', 'email', 'phone', 'kvk', 'website',
                'email_public', 'phone_public', 'website_public',
                'business_type_id',
            ]))->merge([
                'btw' => (string) $request->get('btw', ''),
                'iban' => strtoupper($request->get('iban')),
                'identity_address' => auth_address(),
            ])->toArray()
        );

        if ($media && $media->type == 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationCreated::dispatch($organization);

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

        if ($media = media()->findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        $organization->update(collect($request->only([
            'name', 'email', 'phone', 'kvk', 'btw', 'website',
            'email_public', 'phone_public', 'website_public',
            'business_type_id',
        ]))->merge([
            'iban' => strtoupper($request->get('iban'))
        ])->toArray());

        if ($media && $media->type == 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationUpdated::dispatch($organization);

        return new OrganizationResource($organization);
    }
}
