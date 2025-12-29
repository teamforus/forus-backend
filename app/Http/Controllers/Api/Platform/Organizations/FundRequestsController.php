<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\FundRequestsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\ApproveFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\AssignEmployeeFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DeclineFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DisregardFundRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\FundRequests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\FundRequests\StoreFundRequestNoteRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\BaseIndexFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\Validator\ValidatorFundRequestResource;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\Note;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestQuery;
use App\Searches\FundRequestSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequestsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsValidator', [FundRequest::class, $organization]);

        $search = (new FundRequestSearch($request->only([
            'q', 'state', 'employee_id', 'from', 'to', 'order_by', 'order_dir', 'assigned',
            'identity_id', 'fund_id',
        ])))->setEmployee($request->employee($organization));

        $stateGroup = $request->get('state_group');
        $builder = $search->query();
        $query = $stateGroup ? FundRequestQuery::whereGroupState(clone $builder, $stateGroup) : $builder;

        return ValidatorFundRequestResource::queryCollection($query, $request)->additional([
            'meta' => [
                'totals' => [
                    'all' => (clone $builder)->count(),
                    'pending' => FundRequestQuery::whereGroupStatePending(clone $builder)->count(),
                    'assigned' => FundRequestQuery::whereGroupStateAssigned(clone $builder)->count(),
                    'resolved' => FundRequestQuery::whereGroupStateResolved(clone $builder)->count(),
                ],
            ],
        ]);
    }

    /**
     * Get fund request.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest,
    ): ValidatorFundRequestResource {
        $this->authorize('viewAsValidator', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest);
    }

    /**
     * Get fund request.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     */
    public function formula(
        Organization $organization,
        FundRequest $fundRequest,
    ): JsonResponse {
        $this->authorize('approveAsValidator', [$fundRequest, $organization]);

        return new JsonResponse($fundRequest->formulaPreview());
    }

    /**
     * Assign fund request to employee.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
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
     * Resign employee from fund request.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     * @noinspection PhpUnused
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
     * @param ApproveFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     */
    public function approve(
        ApproveFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): ValidatorFundRequestResource {
        $this->authorize('approveAsValidator', [$fundRequest, $organization]);

        $data = $request->input('fund_amount_preset_id') ?
            $request->only('fund_amount_preset_id') :
            $request->only('amount');

        $fundRequest->forceFill($data)->save();
        $fundRequest->approve();

        if ($request->input('note')) {
            $fundRequest->addNote($request->input('note'), $request->employee($organization));
        }

        return ValidatorFundRequestResource::create($fundRequest);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeclineFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return ValidatorFundRequestResource
     */
    public function decline(
        DeclineFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest->decline(
            $request->input('note'),
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DisregardFundRequestsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     * @noinspection PhpUnused
     */
    public function disregard(
        DisregardFundRequestsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('disregard', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest->disregard(
            $request->input('note'),
            $request->input('notify'),
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     * @noinspection PhpUnused
     */
    public function disregardUndo(
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('disregardUndo', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest->disregardUndo());
    }

    /**
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('exportAnyAsValidator', [FundRequest::class, $organization]);

        return ExportFieldArrResource::collection(FundRequestsExport::getExportFields());
    }

    /**
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function export(
        IndexFundRequestsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('exportAnyAsValidator', [FundRequest::class, $organization]);

        $fields = $request->input('fields', FundRequestsExport::getExportFieldsRaw());
        $fileData = new FundRequestsExport($request, $request->employee($organization), $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
    }

    /**
     * @param AssignEmployeeFundRequestRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     * @noinspection PhpUnused
     */
    public function assignEmployee(
        AssignEmployeeFundRequestRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestResource {
        $this->authorize('assignEmployeeAsSupervisor', [$fundRequest, $organization]);

        /** @var Employee $employee */
        $employee = $organization->employees()->find($request->input('employee_id'));

        return ValidatorFundRequestResource::create($fundRequest->assignEmployee(
            $employee,
            $organization->findEmployee($request->auth_address())
        ));
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestResource
     * @noinspection PhpUnused
     */
    public function resignEmployee(
        BaseFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): ValidatorFundRequestResource {
        $this->authorize('resignEmployeeAsSupervisor', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest->resignEmployee(
            $fundRequest->employee,
            $request->employee($organization),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function notes(
        BaseIndexFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyNoteAsValidator', [$fundRequest, $organization]);

        return NoteResource::queryCollection($fundRequest->notes()->whereRelation('employee', [
            'organization_id' => $organization->id,
        ]), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreFundRequestNoteRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws AuthorizationException
     * @return NoteResource
     * @noinspection PhpUnused
     */
    public function storeNote(
        StoreFundRequestNoteRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): NoteResource {
        $this->authorize('storeNoteAsValidator', [$fundRequest, $organization]);

        return NoteResource::create($fundRequest->addNote(
            $request->input('description'),
            $request->employee($organization),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param Note $note
     * @throws AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function destroyNote(
        Organization $organization,
        FundRequest $fundRequest,
        Note $note,
    ): JsonResponse {
        $this->authorize('destroyNoteAsValidator', [$fundRequest, $organization, $note]);

        $note->delete();

        return new JsonResponse();
    }
}
