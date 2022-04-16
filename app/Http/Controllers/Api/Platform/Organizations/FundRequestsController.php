<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Funds\Requests\AssignEmployeeFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DisregardFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\PersonBSNRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
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
        $this->authorize('viewAnyAsValidator', [FundRequest::class, $organization]);

        return ValidatorFundRequestResource::queryCollection(FundRequest::search(
            $request,
            $request->employee($organization)
        ), $request);
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

        return ValidatorFundRequestResource::create($fundRequest);
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

        return ValidatorFundRequestResource::create($fundRequest->assignEmployee(
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

        return ValidatorFundRequestResource::create($fundRequest->resignEmployee(
            $request->employee($organization)
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

        return ValidatorFundRequestResource::create($fundRequest->approve(
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

        return ValidatorFundRequestResource::create($fundRequest->decline(
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

        return ValidatorFundRequestResource::create($fundRequest->disregard(
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

        return ValidatorFundRequestResource::create($fundRequest->disregardUndo(
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

        $fileData = new FundRequestsExport($request, $request->employee($organization));
        $fileName = date('Y-m-d H:i:s') . '.'. $request->input('export_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
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
        $this->authorize('assignEmployeeAsSupervisor', [$fundRequest, $organization]);

        /** @var Employee $employee */
        $employee = $organization->employees()->find($request->post('employee_id'));

        return ValidatorFundRequestResource::create($fundRequest->assignEmployee(
            $employee,
            $organization->findEmployee($request->auth_address())
        ));
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resignEmployee(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resignEmployeeAsSupervisor', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest->resignAllEmployees(
            $organization,
            $request->employee($organization)
        ));
    }

    /**
     * @param PersonBSNRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function personBsn(
        PersonBSNRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): JsonResponse {
        $this->authorize('viewPersonBSNData', [$fundRequest, $organization]);

        $bsn = resolve('forus.services.record')->bsnByAddress($fundRequest->identity_address);
        $person = $fundRequest->fund->getIConnect()->getPerson($bsn, [
            'parents', 'children', 'partners',
        ]);

        if ($person && ($scope = $request->get('scope'))) {
            $bsn = $person->getBsnByScope($scope, $request->get('scope_id'));
            $person = $bsn ? $fundRequest->fund->getIConnect()->getPerson($bsn) : null;
        }

        return new JsonResponse([
            'data' => $person ? $person->toArray() : null,
        ]);
    }
}
