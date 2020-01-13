<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\Identity\ApproveRecordValidationRequest;
use App\Http\Requests\Api\RecordValidations\RecordValidationStoreRequest;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class RecordValidationController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = resolve('forus.services.record');
    }

    /**
     * Store a newly created resource in storage.
     * @param RecordValidationStoreRequest $request
     * @return \Illuminate\Http\Response|array
     */
    public function store(RecordValidationStoreRequest $request)
    {
        $request = $this->recordRepo->makeValidationRequest(
            auth_address(),
            $request->get('record_id', '')
        );

        if (!$request) {
            return response([
                'message' => 'Can\'t create validation request'
            ])->setStatusCode(403);
        }

        return $request;
    }

    /**
     * Display the specified resource.
     *
     * @param string $recordUuid
     * @return \Illuminate\Http\Response|array
     */
    public function show(
        string $recordUuid
    ) {
        $request = $this->recordRepo->showValidationRequest(
            $recordUuid
        );

        $request['organizations_available'] =
            Organization::queryByIdentityPermissions(
            auth()->id(), 'validate_records'
        )->select(['id', 'name'])->get()->map(function(Organization $organization) {
            return $organization->only(['id', 'name']);
        });

        if (!$request) {
            abort(404, "Not found");
        }

        return $request;
    }

    /**
     * Approve validation request
     *
     * @param ApproveRecordValidationRequest $request
     * @param string $recordUuid
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function approve(
        ApproveRecordValidationRequest $request,
        string $recordUuid
    ) {
        $success = $this->recordRepo->approveValidationRequest(
            auth_address(),
            $recordUuid,
            $request->post('organization_id')
        );

        if (!$success) {
            return response([
                'message' => 'Can\'t approve request.'
            ])->setStatusCode(403);
        }

        return compact('success');
    }

    /**
     * Decline validation request
     * @param string $recordUuid
     * @return mixed
     */
    public function decline(
        string $recordUuid
    ) {
        $success = $this->recordRepo->declineValidationRequest(
            auth_address(),
            $recordUuid
        );

        if (!$success) {
            return response([
                'message' => 'Can\'t decline request.'
            ])->setStatusCode(403);
        }

        return compact('success');
    }
}
