<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Reimbursements\ReimbursementSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Reimbursements\IndexReimbursementsRequest;
use App\Http\Requests\Api\Platform\Reimbursements\StoreReimbursementRequest;
use App\Http\Requests\Api\Platform\Reimbursements\UpdateReimbursementRequest;
use App\Http\Resources\ReimbursementResource;
use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReimbursementsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexReimbursementsRequest $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(IndexReimbursementsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reimbursement::class);

        $builder = Reimbursement::whereRelation('voucher', 'identity_address', $request->auth_address());
        $search = new ReimbursementsSearch($request->only('state', 'fund_id', 'archived'), $builder);

        return ReimbursementResource::queryCollection($search->query()->latest(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReimbursementRequest $request
     * @return ReimbursementResource
     * @throws AuthorizationException
     * @throws \Exception
     */
    public function store(StoreReimbursementRequest $request): ReimbursementResource
    {
        $this->authorize('create', Reimbursement::class);

        $files = $request->input('files');
        $voucher = $request->identity()->vouchers()->find($request->input('voucher_id'));

        $data = $request->only([
            'title', 'description', 'amount', 'email', 'iban', 'iban_name', 'state',
        ]);

        $reimbursement = $voucher->makeReimbursement($data);
        $reimbursement->syncFilesByUid($files);

        return ReimbursementResource::create($reimbursement);
    }

    /**
     * Display the specified resource.
     *
     * @param Reimbursement $reimbursement
     * @return ReimbursementResource
     * @throws AuthorizationException
     */
    public function show(Reimbursement $reimbursement): ReimbursementResource
    {
        $this->authorize('view', $reimbursement);

        return ReimbursementResource::create($reimbursement);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateReimbursementRequest $request
     * @param Reimbursement $reimbursement
     * @return ReimbursementResource
     * @throws AuthorizationException
     */
    public function update(
        UpdateReimbursementRequest $request,
        Reimbursement $reimbursement
    ): ReimbursementResource {
        $this->authorize('update', $reimbursement);

        $submit =
            $reimbursement->isDraft() &&
            $request->input('state') === $reimbursement::STATE_PENDING;

        $data = array_merge($request->only([
            'title', 'description', 'amount', 'email', 'iban', 'iban_name',
            'state', 'voucher_id',
        ]), $submit ? ['submitted_at' => now()] : []);

        $reimbursement->update($data);
        $reimbursement->syncFilesByUid($request->input('files'));

        if ($submit) {
            ReimbursementSubmitted::dispatch($reimbursement);
        }

        return ReimbursementResource::create($reimbursement);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Reimbursement $reimbursement
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Reimbursement $reimbursement): JsonResponse
    {
        $this->authorize('delete', $reimbursement);

        $reimbursement->delete();

        return new JsonResponse();
    }
}