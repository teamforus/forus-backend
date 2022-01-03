<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Funds\Requests\ApproveFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DisregardFundRequestsRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Api\Platform\Funds\Requests\DeclineFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Resources\Validator\ValidatorFundRequestResource;
use App\Http\Controllers\Controller;
use App\Exports\FundRequestsExport;
use App\Models\Organization;
use App\Models\FundRequest;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsValidator', [
            FundRequest::class, $organization
        ]);

        return ValidatorFundRequestResource::collection(FundRequest::search(
            $request, $organization, auth_address()
        )->with(ValidatorFundRequestResource::$load)->paginate(
            $request->input('per_page'))
        );
    }

    /**
     * Get fund request
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('viewAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest);
    }

    /**
     * Assign fund request to employee
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function assign(
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('assignAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->assignEmployee(
            $organization->findEmployee(auth_address())
        ));
    }

    /**
     * Resign employee from fund request
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function resign(
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resignAsValidator', [$fundRequest, $organization]);

        $fundRequest->resignEmployee($organization->findEmployee(auth_address()));

        return new ValidatorFundRequestResource($fundRequest->load(
            ValidatorFundRequestResource::$load
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ApproveFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function approve(
        ApproveFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->approve(
            $organization->findEmployee(auth_address()),
            $request->input('note', '')
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeclineFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function decline(
        DeclineFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->decline(
            $organization->findEmployee(auth_address()),
            $request->input('note', '')
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DisregardFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function disregard(
        DisregardFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('disregard', [$fundRequest]);

        return new ValidatorFundRequestResource($fundRequest->disregard(
            $organization->findEmployee(auth_address()),
            $request->input('note', '')
        ));
    }

    /**
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexFundRequestsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('exportAnyAsValidator', [
            FundRequest::class, $organization
        ]);

        $type = $request->input('export_format', 'xls');

        return resolve('excel')->download(
            new FundRequestsExport($request, $organization, auth_address()),
            date('Y-m-d H:i:s') . '.'. $type
        );
    }
}
