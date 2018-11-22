<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\StoreValidatorRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\Validators\UpdateValidatorRequest;
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
        $this->authorize('update', $organization);
        $this->authorize('index', [Validator::class, $organization]);

        return ValidatorResource::collection($organization->validators);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreValidatorRequest $request
     * @param Organization $organization
     * @return ValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreValidatorRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('store', [Validator::class, $organization]);

        $identity_address = resolve(
            'forus.services.record'
        )->identityIdByEmail($request->input('email'));

        resolve('forus.services.mail_notification')->youAddedAsValidator(
            $identity_address,
            $organization->name
        );

        return new ValidatorResource($organization->validators()->create([
            'identity_address' => $identity_address
        ]));
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
        $this->authorize('update', $organization);
        $this->authorize('show', [$validator, $organization]);

        return new ValidatorResource($validator);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateValidatorRequest $request
     * @param Organization $organization
     * @param Validator $validator
     * @return ValidatorResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateValidatorRequest $request,
        Organization $organization,
        Validator $validator
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', [$validator, $organization]);

        $identity_address = app()->make(
            'forus.services.record'
        )->identityIdByEmail(
            $request->input('email')
        );

        $validator->update($request->only([
            'identity_address' => $identity_address
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
        $this->authorize('destroy', [$validator, $organization]);

        $validator->delete();

        return [];
    }
}
