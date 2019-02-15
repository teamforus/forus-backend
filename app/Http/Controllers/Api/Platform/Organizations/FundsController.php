<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Funds\FundCreated;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\TopUpResource;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

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
        $this->authorize('show', $organization);
        $this->authorize('index', [Fund::class, $organization]);

        return FundResource::collection($organization->funds);
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
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        /** @var Fund $fund */
        $fund = $organization->funds()->create($request->only([
            'name', 'state', 'start_date', 'end_date', 'notification_amount'
        ]));

        $fund->product_categories()->sync(
            $request->input('product_categories', [])
        );

        if ($media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
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
        $this->authorize('show', $organization);
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
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        if ($fund->state == 'waiting') {
            $params = $request->only([
                'name', 'state', 'start_date', 'end_date', 'notification_amount'
            ]);
        } else {
            $params = $request->only([
                'name', 'state', 'notification_amount'
            ]);
        }

        $fund->update($params);

        $fund->product_categories()->sync(
            $request->input('product_categories', [])
        );

        if ($media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
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

        $rangeBetween = function(Carbon $startDate, Carbon $endDate, $countDates) {
            $countDates--;
            $dates = collect();
            $diffBetweenDates = $startDate->diffInDays($endDate);

            $countDates = min($countDates, $diffBetweenDates);
            $interval = $diffBetweenDates / $countDates;

            if ($diffBetweenDates > 1) {
                for ($i = 0; $i < $countDates; $i++) {
                    $dates->push($startDate->copy()->addDays($i * $interval));
                }
            }

            $dates->push($endDate);

            return $dates;
        };

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

            $dates = collect(CarbonPeriod::between($startDate, $endDate)->toArray());
        } elseif ($type == 'all') {
            $firstTransaction = $fund->voucher_transactions()->orderBy(
                'created_at'
            )->first();

            $startDate = $firstTransaction ? $firstTransaction->created_at->subDay() : Carbon::now();
            $endDate = Carbon::now();

            $dates = $rangeBetween($startDate, $endDate, 8);
        } else {
            abort(403, "");
            exit();
        }

        $dates = $dates->map(function (Carbon $date, $key) use ($fund, $dates, $product_category_id) {
            if($key > 0) {
                $voucherQuery = $fund->voucher_transactions()->whereBetween(
                    'voucher_transactions.created_at', [
                        $dates[$key - 1]->copy()->endOfDay(),
                        $date->copy()->endOfDay()
                    ]
                );

                if ($product_category_id) {
                    if($product_category_id == -1){
                        $voucherQuery = $voucherQuery->whereNull('voucher_transactions.product_id');
                    }else {
                        $voucherQuery = $voucherQuery->whereHas('product', function (Builder $query) use ($product_category_id) {
                            return $query->where('product_category_id', $product_category_id);
                        });
                    }
                }

                return [
                    "key" => $date->format('Y-m-d'),
                    "value" => $voucherQuery->sum('voucher_transactions.amount')
                ];
            }

            return [
                "key" => $date->format('Y-m-d'),
                "value" => 0
            ];
        });

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
            'activations' => $fund->vouchers()->whereNull(
                'parent_id'
            )->whereBetween('created_at', [
                $startDate, $endDate
            ])->count(),
            'providers' => $providers->distinct()->groupBy('organization_id')->count()
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

        if ($fund->top_ups()->count() == 0) {
            $topUp = $fund->top_ups()->create([
                'code' => FundTopUp::generateCode()
            ]);
        } else {
            $topUp = $fund->top_ups()->first();
        }

        return new TopUpResource($topUp);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$fund, $organization]);

        $fund->delete();

        return compact('success');
    }
}
