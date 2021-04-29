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
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\MediaService\Models\Media;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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

        $totals = Fund::getFundTotals($organization->funds()->get());

        $totalsBudget = Fund::getFundTotals($organization->funds()->where([
            'type' => Fund::TYPE_BUDGET,
        ])->where('state', '!=', Fund::STATE_WAITING)->getQuery()->get());

        return $request ? response()->json([
            'total_amount'      => currency_format($totals['total_budget']),
            'left'              => currency_format($totals['total_budget_left']),
            'used'              => currency_format($totals['total_budget_used']),
            'reserved'          => currency_format($totals['total_reserved']),
            'transaction_costs' => currency_format($totals['total_transaction_costs']),
            'vouchers_amount'   => currency_format($totals['total_vouchers_amount']),
            'vouchers_active'   => currency_format($totals['total_active_vouchers']),
            'vouchers_inactive' => currency_format($totals['total_inactive_vouchers']),
            'budget' => [
                'total_amount'      => currency_format($totalsBudget['total_budget']),
                'left'              => currency_format($totalsBudget['total_budget_left']),
                'used'              => currency_format($totalsBudget['total_budget_used']),
                'reserved'          => currency_format($totalsBudget['total_reserved']),
                'transaction_costs' => currency_format($totalsBudget['total_transaction_costs']),
                'vouchers_amount'   => currency_format($totalsBudget['total_vouchers_amount']),
                'vouchers_active'   => currency_format($totalsBudget['total_active_vouchers']),
                'vouchers_inactive' => currency_format($totalsBudget['total_inactive_vouchers']),
            ]
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

        $fundsQuery = ($detailed ? $organization->funds()->where([
            'type' => Fund::TYPE_BUDGET
        ]) : $organization->funds())->where('state', '!=', Fund::STATE_WAITING)->getQuery();

        $exportData = new FundsExport(Fund::search($request, $fundsQuery)->get(), $detailed);

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

        $product_category_ids = $request->input('product_category_ids');
        $provider_ids = $request->input('provider_ids');
        $fund_ids = $request->input('fund_ids');
        $postcodes = $request->input('postcodes');

        $type = $request->input('type');
        $type_value = $request->input('type_value');

        $funds = $organization->funds();
        $funds = ($fund_ids ? $funds->whereIn('id', $fund_ids) : $funds)->get();
        $dates = [];

        if ($type === 'year') {
            $startDate = Carbon::createFromDate($type_value)->startOfYear();

            do {
                $dates[] = [
                    'from' => $startDate->copy()->startOfDay(),
                    'to' => $startDate->copy()->endOfMonth()->endOfDay(),
                ];
            } while ($startDate->addMonth()->year == $type_value);
        } // elseif ($type === 'quarter') {
            /*$startDate = Carbon::createFromDate($year, ($nth * 3) - 2, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfQuarter()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addMonths());
            $dates->push($startDate->copy()->addMonths()->addDays(14));
            $dates->push($startDate->copy()->addMonths(2));
            $dates->push($startDate->copy()->addMonths(2)->addDays(14));
            $dates->push($endDate);*/
        // } elseif ($type === 'month') {
            /*$startDate = Carbon::createFromDate($year, $nth, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(4));
            $dates->push($startDate->copy()->addDays(9));
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addDays(19));
            $dates->push($startDate->copy()->addDays(24));
            $dates->push($endDate);*/
        // } elseif ($type === 'week') {
            /*$startDate = Carbon::now()->setISODate(
                $year, $nth
            )->startOfWeek()->startOfDay();
            $endDate = $startDate->copy()->endOfWeek()->endOfDay();

            $dates = range_between_dates($startDate, $endDate);

            $dates->prepend(
                $dates[0]->copy()->subDay()->endOfDay()
            );*/
        // }

        foreach ($dates as $index => $dateItem) {
            /** @var Carbon $dateFrom */
            $dateFrom = $dateItem['from'];

            /** @var Carbon $dateTo */
            $dateTo = $dateItem['to'];

            // global filter sponsor organization
            $voucherQuery = VoucherTransaction::whereHas('voucher', function(Builder $builder) use ($organization) {
                return $builder->whereHas('fund', function(Builder $builder) use ($organization) {
                    $builder->where('funds.organization_id', $organization->id);
                });
            });

            // Filter by selected funds
            $voucherQuery->whereHas('voucher', function(Builder $builder) use ($funds) {
                $builder->whereIn('vouchers.fund_id', $funds->pluck('id')->toArray());
            });

            // Filter by selected provider
            $voucherQuery->whereHas('provider', function(
                Builder $builder
            ) use ($organization, $provider_ids, $postcodes) {
                OrganizationQuery::whereIsProviderOrganization($builder, $organization);

                $provider_ids && $builder->whereIn('id', $provider_ids);
                $postcodes && OrganizationQuery::whereHasPostcodes($builder, $postcodes);
            });

            // filter by interval
            $voucherQuery->whereBetween('voucher_transactions.created_at', [
                $dateFrom, $dateTo
            ]);

            // filter by category (include children categories)
            if ($product_category_ids) {
                $voucherQuery->where(function(Builder $builder) use ($product_category_ids) {
                    $builder->whereHas('product', function(Builder $builder) use ($product_category_ids) {
                        ProductQuery::productCategoriesFilter($builder, $product_category_ids);
                    });

                    $builder->orWhereHas('voucher.product', function(Builder $builder) use ($product_category_ids) {
                        ProductQuery::productCategoriesFilter($builder, $product_category_ids);
                    });
                });
            }

            /**
             * @param $voucherQuery VoucherTransaction|Builder
             * @param $format string
             * @return array
             */
            $getDateData = function(Builder $voucherQuery, string $format) use ($dateFrom, $dateTo): array  {
                $getTransactionData = function(?VoucherTransaction $transaction): ?array {
                    return $transaction ? array_merge($transaction->only('id', 'amount'), [
                        'provider' => $transaction->provider->name,
                    ]) : null;
                };

                $lowest_transaction = clone($voucherQuery);
                $highest_transaction = clone($voucherQuery);
                $highest_daily_transaction = clone($voucherQuery);

                /** @var VoucherTransaction|Model|null $lowest_transaction */
                $lowest_transaction = $lowest_transaction->orderBy('amount')->first();

                /** @var VoucherTransaction|Model|null $highest_transaction */
                $highest_transaction = $highest_transaction->orderByDesc('amount')->first();

                /** @var VoucherTransaction|Model|null $highest_daily_transaction */
                $highest_daily_transaction = $highest_daily_transaction->groupBy(
                    DB::raw('Date(created_at)')
                )->orderByDesc('amount')->select(
                    DB::raw('sum(`voucher_transactions`.`amount`) as `amount`, Date(created_at) as date')
                )->first();

                return [
                    "key" => $dateFrom->copy()->startOfDay()->formatLocalized($format),
                    "amount" => $voucherQuery->sum('voucher_transactions.amount'),
                    "count" => $voucherQuery->count(),
                    "transactions" => $voucherQuery->pluck('amount'),
                    "lowest_transaction" => $getTransactionData($lowest_transaction),
                    "highest_transaction" => $getTransactionData($highest_transaction),
                    "highest_daily_transaction" => $highest_daily_transaction ? array_merge($highest_daily_transaction->toArray(), [
                        'date_locale' => format_date_locale($highest_daily_transaction->date ?? null),
                    ]) : $highest_daily_transaction,
                ];
            };

            $formats = [
                'year' => '%B, %Y',
                'quarter' => '%d %h',
                'month' => '%d',
                'week' => '%A',
            ];

            $dates[$index] = $getDateData($voucherQuery, $formats[$type]);
        }

        // $providers = $providerOrganizations;

        $service_costs = $transaction_costs = 0;
        $funds->each(function (Fund $fund) use (&$service_costs, &$transaction_costs) {
            $service_costs += $fund->getServiceCosts();
            $transaction_costs += $fund->getTransactionCosts();
        });

        /*if ($product_category_ids) {
            $providers = $providers->whereHas('product', function (
                Builder $query
            ) use ($product_category_ids) {
                return $query->where('product_category_id', $product_category_ids);
            });
        }*/

        $dates = collect($dates);

        $lowest_transaction = $dates->pluck('lowest_transaction')->sortBy('amount')->first();
        $highest_transaction = $dates->pluck('highest_transaction')->sortByDesc('amount')->first();
        $highest_daily_transaction = $dates->pluck('highest_daily_transaction')->sortByDesc('amount')->first();

        return response()->json([
            'dates' => $dates->toArray(),
            'totals' => [
                'amount' => $dates->sum('amount'),
                'count' => $dates->sum('count'),
            ],
            "lowest_transaction" => $lowest_transaction,
            "highest_transaction" => $highest_transaction,
            "highest_daily_transaction" => $highest_daily_transaction,
            'service_costs' => [
                'total' => $service_costs,
                'transaction_costs' => $transaction_costs
            ],
            // 'providers' => $providers->count(DB::raw('DISTINCT organization_id'))
        ]);
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
