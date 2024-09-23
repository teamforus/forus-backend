<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\FundRequestsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\ApproveFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\AssignEmployeeFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DeclineFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DisregardFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\FundRequestPersonRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestNoteRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\BaseIndexFormRequest;
use App\Http\Resources\Arr\FundRequestPersonArrResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\Validator\ValidatorFundRequestEmailLogResource;
use App\Http\Resources\Validator\ValidatorFundRequestResource;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\Note;
use App\Models\Organization;
use App\Scopes\Builders\EmailLogQuery;
use App\Searches\FundRequestSearch;
use App\Searches\Sponsor\EmailLogSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsValidator', [FundRequest::class, $organization]);

        $search = (new FundRequestSearch($request->only([
            'q', 'state', 'employee_id', 'from', 'to',
            'order_by', 'order_dir', 'assigned', 'is_resolved',
        ])))->setEmployee($request->employee($organization));

        return ValidatorFundRequestResource::queryCollection(
            $search->query(), $request
        )->additional([
            'meta' => [
                'totals' => FundRequest::makeTotalsMeta($organization),
            ],
        ]);
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
        FundRequest $fundRequest,
    ): ValidatorFundRequestResource {
        $this->authorize('viewAsValidator', [$fundRequest, $organization]);

        return ValidatorFundRequestResource::create($fundRequest);
    }

    /**
     * Get fund request
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function formula(
        Organization $organization,
        FundRequest $fundRequest,
    ): JsonResponse {
        $this->authorize('approveAsValidator', [$fundRequest, $organization]);

        return new JsonResponse($fundRequest->formulaPreview());
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
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
            $request->input('note'),
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
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @noinspection PhpUnused
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
     * @return ValidatorFundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param FundRequestPersonRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return FundRequestPersonArrResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     * @noinspection PhpUnused
     */
    public function person(
        FundRequestPersonRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): FundRequestPersonArrResource {
        $this->authorize('viewPersonBSNData', [$fundRequest, $organization]);

        $iConnect = $fundRequest->fund->getIConnect();
        $bsn = $fundRequest->identity->bsn;
        $person = $iConnect->getPerson($bsn, ['parents', 'children', 'partners']);

        $scope = $request->input('scope');
        $scope_id = $request->input('scope_id');

        if ($person && $person->response()->success() && $scope && $scope_id) {
            if (!$relation = $person->getRelatedByIndex($scope, $scope_id)) {
                abort(404, 'Relation not found.');
            }

            $person = $relation->getBSN() ? $iConnect->getPerson($relation->getBSN()) : $relation;
        }

        if (!$person || $person->response() && $person->response()->error()) {
            if ($person && $person->response()->getCode() === 404) {
                abort(404, 'iConnect error, person not found.');
            }

            abort(400, $person ? 'iConnect, unknown error.' : 'iConnect, connection error.');
        }

        return new FundRequestPersonArrResource($person);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
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
     * @return NoteResource
     * @throws AuthorizationException
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
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return AnonymousResourceCollection
     */
    public function emailLogs(
        BaseIndexFormRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyEmailLogs', [$fundRequest, $organization]);

        $query = EmailLogQuery::whereFundRequest(EmailLog::query(), $fundRequest);
        $search = new EmailLogSearch($request->only(['q']), $query);

        return ValidatorFundRequestEmailLogResource::queryCollection($search->query(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param EmailLog $emailLog
     * @return Response
     */
    public function exportEmailLog(
        Organization $organization,
        FundRequest $fundRequest,
        EmailLog $emailLog
    ): Response {
        $this->authorize('exportEmailLog', [$fundRequest, $organization, $emailLog]);

        return $emailLog->toPdf()->download('email.pdf');
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param Note $note
     * @return JsonResponse
     * @throws AuthorizationException
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
