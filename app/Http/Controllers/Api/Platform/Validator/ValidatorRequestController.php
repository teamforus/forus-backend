<?php

namespace App\Http\Controllers\Api\Platform\Validator;

use App\Http\Requests\Api\Platform\Validator\ValidatorRequest\ValidateValidatorRequestRequest;
use App\Http\Resources\Validator\ValidatorRequestResource;
use App\Models\Validator;
use App\Models\ValidatorRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ValidatorRequestController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Display a listing of the resource.$organization
     *
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request
    ) {
        $this->authorize('index', ValidatorRequest::class);

        $validatorIds = Validator::getModel()->where(
            'identity_address',
            auth()->user()->getAuthIdentifier()
        )->pluck('id');

        $validatorRequest = ValidatorRequest::getModel()->whereIn(
            'validator_id', $validatorIds
        );

        if ($state = $request->get('state')) {
            $validatorRequest->where('state', $state);
        }

        return ValidatorRequestResource::collection($validatorRequest->get());
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

    /**
     * Update the specified resource in storage.
     *
     * @param ValidateValidatorRequestRequest $request
     * @param ValidatorRequest $validatorRequest
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        ValidateValidatorRequestRequest $request,
        ValidatorRequest $validatorRequest
    ) {
        $this->authorize('validate', $validatorRequest);

        $state = $request->input('state', false);

        if (in_array($state, ['approved', 'declined'])) {
            $validationRequest = $this->recordRepo->makeValidationRequest(
                $validatorRequest->identity_address,
                $validatorRequest->record_id
            );

            if ($state == 'approved') {
                $this->recordRepo->approveValidationRequest(
                    auth()->user()->getAuthIdentifier(),
                    $validationRequest['uuid']
                );
            } else {
                $this->recordRepo->approveValidationRequest(
                    auth()->user()->getAuthIdentifier(),
                    $validationRequest['uuid']
                );
            }

            $validatorRequest->update([
                'state' => $state,
                'record_validation_uid' => $validationRequest['uuid']
            ]);
        }

        return new ValidatorRequestResource($validatorRequest);
    }
}
