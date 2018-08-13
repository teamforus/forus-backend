<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\StoreValidatorRule;
use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\UpdateValidatorRule;
use App\Http\Resources\ValidatorResource;
use App\Models\Validator;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class ValidatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('index', Validator::class);

        return ValidatorResource::collection($organization->validators);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreValidatorRule $request
     * @param Organization $organization
     * @return ValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreValidatorRule $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('store', Validator::class);

        return new ValidatorResource($organization->validators()->create(
            $request->only([
                'identity_address'
            ])
        ));
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Validator $validator
     * @return ValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Validator $validator
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $validator);

        return new ValidatorResource($validator);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateValidatorRule $request
     * @param Organization $organization
     * @param Validator $validator
     * @return ValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateValidatorRule $request,
        Organization $organization,
        Validator $validator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $validator);

        $validator->update($request->only([
            'identity_address'
        ]));

        return new ValidatorResource($validator);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Validator $validator
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Validator $validator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('destroy', $validator);

        $validator->delete();

        return [];
    }
}
