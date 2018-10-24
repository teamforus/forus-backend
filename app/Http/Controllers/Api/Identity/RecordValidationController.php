<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\RecordValidations\RecordValidationStoreRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecordValidationController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  RecordValidationStoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RecordValidationStoreRequest $request)
    {
        $request = $this->recordRepo->makeValidationRequest(
            auth()->user()->getAuthIdentifier(),
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
     * @return \Illuminate\Http\Response
     */
    public function show(
        string $recordUuid
    ) {
        $request = $this->recordRepo->showValidationRequest(
            $recordUuid
        );

        if (!$request) {
            abort(404, "Not found");
        }

        return $request;
    }

    /**
     * Approve validation request
     * @param Request $request
     * @param string $recordUuid
     * @return mixed
     */
    public function approve(
        Request $request,
        string $recordUuid
    ) {
        $success = $this->recordRepo->approveValidationRequest(
            auth()->user()->getAuthIdentifier(),
            $recordUuid
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
     * @param Request $request
     * @param string $recordUuid
     * @return mixed
     */
    public function decline(
        Request $request,
        string $recordUuid
    ) {
        $success = $this->recordRepo->declineValidationRequest(
            auth()->user()->getAuthIdentifier(),
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
