<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Requests\Api\Platform\Organizations\Provider\Identities\StoreProviderIdentityRequest;
use App\Http\Requests\Api\Platform\Organizations\Provider\Identities\UpdateProviderIdentityRequest;
use App\Http\Resources\Provider\ProviderIdentityResource;
use App\Models\Organization;
use App\Models\ProviderIdentity;
use App\Http\Controllers\Controller;

class ProviderIdentitiesController extends Controller
{
    /**
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('index', [ProviderIdentity::class, $organization]);

        return ProviderIdentityResource::collection(
            $organization->provider_identities
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProviderIdentityRequest $request
     * @param Organization $organization
     * @return ProviderIdentityResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProviderIdentityRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('store', [ProviderIdentity::class, $organization]);

        $identity_address = app()->make(
            'forus.services.record'
        )->identityIdByEmail(
            $request->input('email')
        );

        $provider_identity = $organization->provider_identities()->create(
            compact('identity_address')
        );

        return new ProviderIdentityResource($provider_identity);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param ProviderIdentity $providerIdentity
     * @return ProviderIdentityResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        ProviderIdentity $providerIdentity
    ) {
        $this->authorize('update', $organization);
        $this->authorize('show', [$providerIdentity, $organization]);

        return new ProviderIdentityResource($providerIdentity);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateProviderIdentityRequest $request
     * @param Organization $organization
     * @param ProviderIdentity $providerIdentity
     * @return ProviderIdentityResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateProviderIdentityRequest $request,
        Organization $organization,
        ProviderIdentity $providerIdentity
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', [$providerIdentity, $organization]);

        $identity_address = app()->make(
            'forus.services.record'
        )->identityIdByEmail(
            $request->input('email')
        );

        $providerIdentity->update(compact('identity_address'));

        return new ProviderIdentityResource($providerIdentity);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param ProviderIdentity $providerIdentity
     * @throws \Exception
     */
    public function destroy(
        Organization $organization,
        ProviderIdentity $providerIdentity
    ) {
        $this->authorize('update', $organization);
        $this->authorize('destroy', [$providerIdentity, $organization]);

        $providerIdentity->delete();
    }
}
