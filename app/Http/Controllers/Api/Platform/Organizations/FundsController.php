<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Funds\FundCreated;
use App\Exports\FundsExport;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceOverviewRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundCriteriaRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundCriteriaRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\IndexFundRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\TopUpResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Scopes\Builders\FundQuery;
use App\Services\MediaService\Models\Media;
use App\Statistics\Funds\FinancialStatistic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class FundsController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class FundsController extends Controller
{
    /**
     *  Display a listing of the resource.
     *
     * @param IndexFundRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Fund::class, $organization]);
        $query = Fund::search($request, $organization->funds()->getQuery());

        if (!auth()->id()) {
            $query->where([
                'public' => true
            ]);
        }

        return FundResource::collection(FundQuery::sortByState($query, [
            'active', 'waiting', 'paused', 'closed'
        ])->paginate($request->input('per_page')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequest $request
     * @param Organization $organization
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundRequest $request,
        Organization $organization
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('store', [Fund::class, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = resolve('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $auto_requests_validation =
            !$request->input('default_validator_employee_id') ? null :
                $request->input('auto_requests_validation');

        /** @var Fund $fund */
        $fund = $organization->funds()->create(array_merge($request->only([
            'name', 'description', 'state', 'start_date', 'end_date', 'type',
            'notification_amount', 'default_validator_employee_id',
        ], [
            'state' => Fund::STATE_WAITING,
            'auto_requests_validation' => $auto_requests_validation
        ])));

        if ($media instanceof Media && $media->type === 'fund_logo') {
            $fund->attachMedia($media);
        }

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->syncCriteria($request->input('criteria'));
        }

        if (config('forus.features.dashboard.organizations.funds.formula_products') &&
            $request->has('formula_products')) {
            $fund->updateFormulaProducts($request->input('formula_products', []));
        }

        FundCreated::dispatch($fund);

        return new FundResource($fund);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', [$fund, $organization]);

        return new FundResource($fund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$fund, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = resolve('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $auto_requests_validation =
            !$request->input('default_validator_employee_id') ? null :
                $request->input('auto_requests_validation');

        if ($fund->state === Fund::STATE_WAITING) {
            $params = array_merge($request->only([
                'name', 'description', 'start_date', 'end_date',
                'notification_amount', 'default_validator_employee_id'
            ]), [
                'auto_requests_validation' => $auto_requests_validation
            ]);
        } else {
            $params = array_merge($request->only([
                'name', 'description', 'notification_amount',
                'default_validator_employee_id'
            ]), [
                'auto_requests_validation' => $auto_requests_validation
            ]);
        }

        $fund->update($params);

        if ($media instanceof Media && $media->type === 'fund_logo') {
            $fund->attachMedia($media);
        }

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->syncCriteria($request->input('criteria'));
        }

        if (config('forus.features.dashboard.organizations.funds.formula_products') &&
            $request->has('formula_products')) {
            $fund->updateFormulaProducts($request->input('formula_products', []));
        }

        return new FundResource($fund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundCriteriaRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateCriteria(
        UpdateFundCriteriaRequest $request,
        Organization $organization,
        Fund $fund
    ): FundResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$fund, $organization]);

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->syncCriteria($request->input('criteria'));
        }

        return new FundResource($fund);
    }

    /**
     * @param StoreFundCriteriaRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeCriteriaValidate(
        StoreFundCriteriaRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('show', $organization);

        return response()->json([], $request ? 200 : 403);
    }

    /**
     * @param UpdateFundCriteriaRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateCriteriaValidate(
        UpdateFundCriteriaRequest $request,
        Organization $organization,
        Fund $fund
    ): JsonResponse {
        $this->authorize('show', $organization);

        return response()->json([], $request && $fund ? 200 : 403);
    }

    /**
     * @param FinanceOverviewRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @noinspection PhpUnused
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function financesOverview(
        FinanceOverviewRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $fundsQuery = $organization->funds()->where('state', '!=', Fund::STATE_WAITING);
        $activeFundsQuery = $organization->funds()->where([
            'type' => Fund::TYPE_BUDGET,
        ])->where('state', '=', Fund::STATE_ACTIVE);

        return $request ? response()->json([
            'funds' => Fund::getFundTotals($fundsQuery->get()),
            'budget_funds' => Fund::getFundTotals($activeFundsQuery->get()),
        ]) : response()->json([], 403);
    }

    /**
     * Export funds data
     *
     * @param FinanceOverviewRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @noinspection PhpUnused
     */
    public function financesOverviewExport(
        FinanceOverviewRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $detailed = $request->input('detailed', false);
        $exportType = $request->input('export_type', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.'. $exportType;

        $fundsQuery = $organization->funds()->where('state', '!=', Fund::STATE_WAITING);
        $activeFundsQuery = $organization->funds()->where([
            'type' => Fund::TYPE_BUDGET,
        ])->where('state', '=', Fund::STATE_ACTIVE);

        $exportData = new FundsExport(($detailed ? $activeFundsQuery : $fundsQuery)->get(), $detailed);

        return resolve('excel')->download($exportData, $fileName);
    }

    /**
     * @param FinanceRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function finances(FinanceRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);

        $filters = $request->input('filters');
        $financialStatistic = new FinancialStatistic();

        $data = $filters ? $financialStatistic->getFilters($organization, $request->only([
            'type', 'type_value',
        ])) : $financialStatistic->getStatistics($organization, $request->only([
            'type', 'type_value', 'fund_ids', 'postcodes', 'provider_ids', 'product_category_ids'
        ]));

        return response()->json($data);
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @return TopUpResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function topUp(Organization $organization, Fund $fund): TopUpResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);

        return new TopUpResource($fund->top_up_model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(Organization $organization, Fund $fund): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$fund, $organization]);

        $fund->fund_formula_products()->delete();
        $fund->delete();

        return response()->json([]);
    }
}
