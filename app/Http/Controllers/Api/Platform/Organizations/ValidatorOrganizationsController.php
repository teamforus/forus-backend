<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\ValidatorOrganizations\IndexValidatorOrganizationsRequest;
use App\Http\Requests\Api\Platform\Organizations\ValidatorOrganizations\StoreValidatorOrganizationsRequest;
use App\Http\Resources\OrganizationValidatorResource;
use App\Models\Organization;
use App\Models\OrganizationValidator;

class ValidatorOrganizationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexValidatorOrganizationsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexValidatorOrganizationsRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);

        return OrganizationValidatorResource::collection(
            $organization->organization_validators()->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param OrganizationValidator $organizationValidator
     * @return OrganizationValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        OrganizationValidator $organizationValidator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('view', $organizationValidator);

        return new OrganizationValidatorResource($organizationValidator);
    }

    /**
     * @param StoreValidatorOrganizationsRequest $request
     * @param Organization $organization
     * @return OrganizationValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreValidatorOrganizationsRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);

        $validatorOrganization = $organization->organization_validators()->firstOrCreate([
            'validator_organization_id' => $request->input('organization_id')
        ]);

        return new OrganizationValidatorResource($validatorOrganization);
    }

    /**
     * @param Organization $organization
     * @param Organization $validatorOrganization
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        Organization $organization,
        Organization $validatorOrganization
    ) {
        $this->authorize('update', $organization);

        $organization->detachExternalValidator($validatorOrganization);

        return response()->json([], 200);
    }
}
