<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Http\Requests\Api\Platform\Organizations\IndexOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationBusinessTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRolesRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationsController extends Controller
{
    /**
     * Display a listing of all identity organizations.
     *
     * @param IndexOrganizationRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexOrganizationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        return OrganizationResource::collection(
            Organization::searchQuery($request)->with(
                OrganizationResource::load($request)
            )->paginate($request->input('per_page', 10))
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
    ): OrganizationResource {
        $this->authorize('store', Organization::class);

        if ($media = media()->findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        /** @var Organization $organization */
        $organization = Organization::create(
            collect($request->only([
                'name', 'email', 'phone', 'kvk', 'website', 'description',
                'email_public', 'phone_public', 'website_public',
                'business_type_id',
            ]))->merge([
                'btw' => (string) $request->get('btw', ''),
                'iban' => strtoupper($request->get('iban')),
                'identity_address' => auth_address(),
            ])->toArray()
        );

        if ($media instanceof Media && $media->type === 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationCreated::dispatch($organization);

        return new OrganizationResource($organization);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Request $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('show', $organization);

        return new OrganizationResource(
            $organization->load(OrganizationResource::load($request))
        );
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
    ): OrganizationResource {
        $this->authorize('update', $organization);

        if ($media = media()->findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        $organization->update(collect($request->only([
            'name', 'email', 'phone', 'kvk', 'btw', 'website',
            'email_public', 'phone_public', 'website_public',
            'business_type_id', 'description',
        ]))->merge([
            'iban' => strtoupper($request->get('iban'))
        ])->toArray());

        if ($media instanceof Media && $media->type === 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationUpdated::dispatch($organization);

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationBusinessTypeRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateBusinessType(
        UpdateOrganizationBusinessTypeRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'business_type_id',
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationRolesRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateRoles(
        UpdateOrganizationRolesRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'is_sponsor', 'is_provider', 'is_validator',
            'validator_auto_accept_funds'
        ])));

        return new OrganizationResource($organization);
    }
}
