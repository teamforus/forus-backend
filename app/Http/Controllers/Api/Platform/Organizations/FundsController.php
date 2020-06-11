<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Funds\FundCreated;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\TopUpResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\MediaService\Models\Media;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Class FundsController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return string
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('viewAny', [Fund::class, $organization]);

        if (auth()->id()) {
            return FundResource::collection(Fund::sortByState($organization->funds));
        }

        return FundResource::collection(
            Fund::sortByState($organization->funds()->where([
                'public' => true
            ])->get())
        );
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
    ) {
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
            'name', 'description', 'state', 'start_date', 'end_date',
            'notification_amount', 'default_validator_employee_id'
        ], [
            'state' => Fund::STATE_WAITING,
            'auto_requests_validation' => $auto_requests_validation
        ])));

        if ($media instanceof Media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
        }

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->makeCriteria($request->input('criteria'));
        }

        if (config('forus.features.dashboard.organizations.funds.formula_products')) {
            if ($request->has('formula_products')) {
                $fund->updateFormulaProducts($request->input('formula_products', []));
            }
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
    ) {
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
    ) {
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

        if ($fund->state == Fund::STATE_WAITING) {
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

        if ($media instanceof Media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
        }

        if (config('forus.features.dashboard.organizations.funds.criteria')) {
            $fund->updateCriteria($request->input('criteria'));
        }

        if (config('forus.features.dashboard.organizations.funds.formula_products')) {
            if ($request->has('formula_products')) {
                $fund->updateFormulaProducts($request->input('formula_products', []));
            }
        }

        return new FundResource($fund);
    }

    /**
     * @param FinanceRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function finances(
        FinanceRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', [$fund, $organization]);

        $dates = collect();

        $type = $request->input('type');
        $year = $request->input('year');
        $nth = $request->input('nth', 1);
        $product_category_id = $request->input('product_category');

        if ($type == 'quarter') {
            $startDate = Carbon::createFromDate($year, ($nth * 3) - 2, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfQuarter()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addMonths(1));
            $dates->push($startDate->copy()->addMonths(1)->addDays(14));
            $dates->push($startDate->copy()->addMonths(2));
            $dates->push($startDate->copy()->addMonths(2)->addDays(14));
            $dates->push($endDate);
        } elseif ($type == 'month') {
            $startDate = Carbon::createFromDate($year, $nth, 1)->startOfDay();
            $endDate = $startDate->copy()->endOfMonth()->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addDays(4));
            $dates->push($startDate->copy()->addDays(9));
            $dates->push($startDate->copy()->addDays(14));
            $dates->push($startDate->copy()->addDays(19));
            $dates->push($startDate->copy()->addDays(24));
            $dates->push($endDate);
        } elseif ($type == 'week') {
            $startDate = Carbon::now()->setISODate(
                $year, $nth
            )->startOfWeek()->startOfDay();
            $endDate = $startDate->copy()->endOfWeek()->endOfDay();

            $dates = range_between_dates($startDate, $endDate);

            $dates->prepend(
                $dates[0]->copy()->subDay()->endOfDay()
            );

        } elseif ($type == 'all') {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();

            $dates->push($startDate);
            $dates->push($startDate->copy()->addQuarters(1));
            $dates->push($startDate->copy()->addQuarters(2));
            $dates->push($startDate->copy()->addQuarters(3));
            $dates->push($endDate);
        } else {
            abort(403, "");
            exit();
        }

        if ($product_category_id == -1) {
            $categories = false;
        } elseif ($product_category_id) {
            $categories = ProductCategory::find($product_category_id)
                ->descendants->pluck('id')->push($product_category_id);
        } else {
            $categories = null;
        }

        $dates = $dates->map(function (Carbon $date, $key) use (
            $fund, $dates, $categories, $type
        ) {
            $previousIntervalEntry = $date;
            if ($key === 0 && $type !== 'week') {
                return [
                    "key" => null,
                    "value" => null
                ];
            } elseif ($key > 0) {
                $previousIntervalEntry = $dates[$key - 1];
            }

            $voucherQuery = $fund->voucher_transactions()->whereBetween(
                'voucher_transactions.created_at', [
                    $previousIntervalEntry->copy()->endOfDay(),
                    $date->copy()->endOfDay()
                ]
            );

            if ($categories === false) {
                $voucherQuery = $voucherQuery->whereNull('voucher_transactions.product_id');
            } else if ($categories) {
                $voucherQuery = $voucherQuery->whereHas('product', function (Builder $query) use ($categories) {
                    return $query->whereIn('product_category_id', $categories->toArray());
                });
            }

            switch ($type) {
                case 'quarter':
                    return [
                        "key" => trans('time.to', [
                            'from_time' => $previousIntervalEntry->copy()->endOfDay()->formatLocalized('%d %h'),
                            'to_time' =>  $date->formatLocalized('%d %h')
                        ]),
                        "value" => $voucherQuery->sum('voucher_transactions.amount')
                    ];
                case 'month':
                    return [
                        "key" => trans('time.to', [
                            'from_time' => $previousIntervalEntry->copy()->endOfDay()->formatLocalized('%d'),
                            'to_time' =>  $date->formatLocalized('%d')
                        ]),
                        "value" => $voucherQuery->sum('voucher_transactions.amount')
                    ];
                case 'week':
                    return [
                        "key" => $date->formatLocalized('%A'),
                        "value" => $voucherQuery->sum('voucher_transactions.amount')
                    ];
                case 'all':
                    return [
                        "key" => trans('time.quarter') . " " . $key,
                        "value" => $voucherQuery->sum('voucher_transactions.amount')
                    ];
                default:
                    return [
                        "key" => $date->formatLocalized('Y-m-d'),
                        "value" => $voucherQuery->sum('voucher_transactions.amount')
                    ];
            }
        });
        $dates->shift();

        $providers = $fund->voucher_transactions()->whereBetween(
            'voucher_transactions.created_at', [
            $startDate, $endDate
        ]);

        if($product_category_id){
            if($product_category_id == -1){
                $providers = $providers->whereNull('voucher_transactions.product_id');
            }else{
                $providers = $providers->whereHas('product', function (Builder $query) use($product_category_id){
                    return $query->where('product_category_id', $product_category_id);
                });
            }
        }

        return [
            'dates' => $dates,
            'usage' => $dates->sum('value'),
            'service_costs' => [
                'total' => $fund->getServiceCosts(),
                'transaction_costs' => $fund->getTransactionCosts()
            ],
            'activations' => $fund->vouchers()->whereNull(
                'parent_id'
            )->whereBetween('created_at', [
                $startDate, $endDate
            ])->count(),
            'providers' => $providers->count(DB::raw('DISTINCT organization_id'))
        ];
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function topUp(
        Organization $organization,
        Fund $fund
    ) {
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
    public function destroy(
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$fund, $organization]);

        $fund->fund_formula_products()->delete();
        $fund->delete();

        return response()->json([], 200);
    }
}
