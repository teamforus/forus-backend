<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Funds\Requests\AssignEmployeeFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DisregardFundRequestsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\FundRequestRecord;
use App\Scopes\Builders\FundRequestRecordQuery;
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
            $request, $organization, $request->auth_address()
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
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function assign(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('assignAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->assignEmployee(
            $request->employee($organization)
        ));
    }

    /**
     * Resign employee from fund request
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resign(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resignAsValidator', [$fundRequest, $organization]);

        $fundRequest->resignEmployee($request->employee($organization));

        return new ValidatorFundRequestResource($fundRequest->load(
            ValidatorFundRequestResource::$load
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function approve(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->approve(
            $request->employee($organization)
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
            $request->employee($organization),
            $request->input('note')
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
        $this->authorize('disregard', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->disregard(
            $request->employee($organization),
            $request->input('note'),
            $request->input('notify')
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function disregardUndo(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('disregardUndo', [$fundRequest, $organization]);

        return new ValidatorFundRequestResource($fundRequest->disregardUndo(
            $request->employee($organization)
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
        $this->authorize('exportAnyAsValidator', [FundRequest::class, $organization]);

        $type = $request->input('export_format', 'xls');

        return resolve('excel')->download(
            new FundRequestsExport($request, $organization, $request->auth_address()),
            date('Y-m-d H:i:s') . '.'. $type
        );
    }

    /**
     * @param AssignEmployeeFundRequestRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assignEmployee(
        AssignEmployeeFundRequestRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('assignEmployeeAsValidator', [
            $fundRequest, $organization, $request->get('employee')
        ]);

        return new ValidatorFundRequestResource($fundRequest->assignEmployee(
            $organization->findEmployee($request->get('employee'))
        ));
    }

    /**
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resignEmployee(
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resignEmployeeAsValidator', [
            $fundRequest, $organization
        ]);

        /** @var FundRequestRecord $recordAssigned */
        $recordAssigned = FundRequestRecordQuery::whereHasAssignedOrganizationEmployeeFilter(
            $fundRequest->records()->getQuery(),
            $fundRequest->fund->organization_id
        )->first();

        $fundRequest->resignEmployee($recordAssigned->employee);

        return new ValidatorFundRequestResource($fundRequest->load(
            ValidatorFundRequestResource::$load
        ));
    }
}
