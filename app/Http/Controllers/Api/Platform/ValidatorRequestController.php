<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\ValidatorRequest\StoreValidatorRequestRequest;
use App\Http\Resources\ValidatorRequestResource;
use App\Models\ValidatorRequest;
use App\Http\Controllers\Controller;

class ValidatorRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', ValidatorRequest::class);

        $validatorRequest = ValidatorRequest::query()->where([
            'identity_address' => auth_address()
        ])->get();

        return ValidatorRequestResource::collection($validatorRequest);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreValidatorRequestRequest $request
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreValidatorRequestRequest $request
    ) {
        $this->authorize('request', ValidatorRequest::class);

        $validatorRequest = ValidatorRequest::create([
            'record_id' => $request->input('record_id'),
            'identity_address' => auth_address(),
            'validator_id' => $request->input('validator_id'),
            'state' => 'pending'
        ]);
        $email = resolve('forus.services.record')->primaryEmailByAddress(
            auth_address()
        );

        resolve('forus.services.notification')->newValidationRequest(
            $email,
            $validatorRequest->validator->identity_address,
            config('forus.front_ends.panel-validator')
        );

        return new ValidatorRequestResource($validatorRequest);
    }

    /**
     * Display the specified resource.
     *
     * @param ValidatorRequest $validatorRequest
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        ValidatorRequest $validatorRequest
    ) {
        $this->authorize('show', $validatorRequest);

        return new ValidatorRequestResource($validatorRequest);
    }
}
