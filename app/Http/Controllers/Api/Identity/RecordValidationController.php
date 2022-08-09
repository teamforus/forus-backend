<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\ApproveRecordValidationRequest;
use App\Http\Requests\Api\RecordValidations\RecordValidationStoreRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\RecordValidationResource;
use App\Models\Organization;
use App\Models\Record;
use App\Models\RecordValidation;
use Illuminate\Http\JsonResponse;

class RecordValidationController extends Controller
{
    /**
     * @param RecordValidationStoreRequest $request
     * @return JsonResponse
     */
    public function store(RecordValidationStoreRequest $request): JsonResponse
    {
        $record = Record::find($request->get('record_id'));
        $validationRequest = $record->makeValidationRequest();

        return new JsonResponse($validationRequest->only('uuid'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @param string $recordUuid
     * @return JsonResponse
     */
    public function show(BaseFormRequest $request, string $recordUuid): JsonResponse
    {
        $identityAddress = $request->auth_address();
        $validationRequest = RecordValidation::whereUuid($recordUuid)->first();

        if (!$validationRequest) {
            return new JsonResponse(['message' => "Not found"], 404);
        }

        $organizations = Organization::queryByIdentityPermissions(
            $identityAddress, 'validate_records',
        )->select('id', 'name')->get();

        $organizations = $organizations->map(function (Organization $organization) {
            return $organization->only('id', 'name');
        });

        return new JsonResponse(array_merge(array_merge($validationRequest->only([
            'state', 'identity_address', 'uuid'
        ]), $validationRequest->record->only([
            'value'
        ]), $validationRequest->record->record_type->only([
            'key', 'name'
        ])), [
            'organizations_available' => $organizations,
        ]));
    }

    /**
     * Approve validation request
     *
     * @param ApproveRecordValidationRequest $request
     * @param string $recordUuid
     * @return JsonResponse
     */
    public function approve(ApproveRecordValidationRequest $request, string $recordUuid): JsonResponse
    {
        $identity = $request->identity();
        $organization = Organization::find($request->post('organization_id'));
        $success = RecordValidation::findByUuid($recordUuid)?->approve($identity, $organization);

        if (!$success) {
            return new JsonResponse(['message' => "Can't approve request."], 403);
        }

        return new JsonResponse(compact('success'));
    }

    /**
     * Decline validation request
     * @param BaseFormRequest $request
     * @param string $recordUuid
     * @return JsonResponse
     */
    public function decline(BaseFormRequest $request, string $recordUuid): JsonResponse
    {
        $success = RecordValidation::findByUuid($recordUuid)?->decline($request->identity());

        if (!$success) {
            return new JsonResponse(['message' => "Can't decline request.",], 403);
        }

        return new JsonResponse(compact('success'));
    }
}
