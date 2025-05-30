<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\ReimbursementsSponsorExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\ApproveReimbursementsRequest;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\DeclineReimbursementsRequest;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\IndexReimbursementsRequest;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\StoreReimbursementNoteRequest;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\UpdateReimbursementRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\BaseIndexFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\Sponsor\SponsorReimbursementResource;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ReimbursementsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexReimbursementsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexReimbursementsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsSponsor', [Reimbursement::class, $organization]);

        $query = Reimbursement::where('state', '!=', Reimbursement::STATE_DRAFT);
        $query = $query->whereRelation('voucher.fund', 'organization_id', $organization->id);

        $search = new ReimbursementsSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'amount_min', 'amount_max', 'state',
            'expired', 'archived', 'deactivated', 'identity_address', 'implementation_id',
        ]), $query);

        return SponsorReimbursementResource::queryCollection($search->query()->latest(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws AuthorizationException
     * @return SponsorReimbursementResource
     */
    public function show(
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('viewAsSponsor', [$reimbursement, $organization]);

        return SponsorReimbursementResource::create($reimbursement);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateReimbursementRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws AuthorizationException
     * @return SponsorReimbursementResource
     */
    public function update(
        UpdateReimbursementRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('viewAsSponsor', [$reimbursement, $organization]);
        $this->authorize('updateAsSponsor', [$reimbursement, $organization]);

        $reimbursement->update($request->only([
            'provider_name', 'reimbursement_category_id',
        ]));

        return SponsorReimbursementResource::create($reimbursement);
    }

    /**
     * Assign fund request to employee.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return SponsorReimbursementResource
     */
    public function assign(
        BaseFormRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('assign', [$reimbursement, $organization]);

        return SponsorReimbursementResource::create($reimbursement->assign(
            $request->employee($organization)
        ));
    }

    /**
     * Resign employee from fund request.
     *
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorReimbursementResource
     * @noinspection PhpUnused
     */
    public function resign(
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('resign', [$reimbursement, $organization]);

        return SponsorReimbursementResource::create($reimbursement->resign());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ApproveReimbursementsRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws Throwable
     * @return SponsorReimbursementResource
     */
    public function approve(
        ApproveReimbursementsRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('resolve', [$reimbursement, $organization]);

        return SponsorReimbursementResource::create($reimbursement->approve(
            $request->input('note')
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeclineReimbursementsRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws Throwable
     * @return SponsorReimbursementResource
     */
    public function decline(
        DeclineReimbursementsRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): SponsorReimbursementResource {
        $this->authorize('resolve', [$reimbursement, $organization]);

        return SponsorReimbursementResource::create($reimbursement->decline(
            $request->input('note'),
            $request->input('reason'),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function notes(
        BaseIndexFormRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyNoteAsSponsor', [$reimbursement, $organization]);

        return NoteResource::queryCollection($reimbursement->notes(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreReimbursementNoteRequest $request
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @throws AuthorizationException
     * @return NoteResource
     * @noinspection PhpUnused
     */
    public function storeNote(
        StoreReimbursementNoteRequest $request,
        Organization $organization,
        Reimbursement $reimbursement
    ): NoteResource {
        $this->authorize('storeNoteAsSponsor', [$reimbursement, $organization]);

        return NoteResource::create($reimbursement->addNote(
            $request->input('description'),
            $request->employee($organization),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Reimbursement $reimbursement
     * @param Note $note
     * @throws AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function destroyNote(
        Organization $organization,
        Reimbursement $reimbursement,
        Note $note,
    ): JsonResponse {
        $this->authorize('destroyNoteAsSponsor', [$reimbursement, $organization, $note]);

        $note->delete();

        return new JsonResponse();
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyAsSponsor', [Reimbursement::class, $organization]);

        return ExportFieldArrResource::collection(ReimbursementsSponsorExport::getExportFields());
    }

    /**
     * @param IndexReimbursementsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function export(
        IndexReimbursementsRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnyAsSponsor', [Reimbursement::class, $organization]);

        $fields = $request->input('fields', ReimbursementsSponsorExport::getExportFieldsRaw());
        $fileData = new ReimbursementsSponsorExport($request, $organization, $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
    }
}
