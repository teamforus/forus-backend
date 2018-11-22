<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\StoreFundRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundRequest;
use App\Http\Resources\FundResource;
use App\Http\Resources\TopUpResource;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\OrganizationProductCategory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
            'name', 'state', 'start_date', 'end_date'
        ]));

        $fund->product_categories()->sync(
            $request->input('product_categories', [])
        );

        $fund->criteria()->create([
            'record_type_key' => 'kindpakket_2018_eligible',
            'value' => "Ja",
            'operator' => '='
        ]);

        $fund->criteria()->create([
            'record_type_key' => 'children_nth',
            'value' => 1,
            'operator' => '>='
        ]);

        if ($media && $media->type == 'fund_logo') {
            $fund->attachMedia($media);
        }

        $organizations = Organization::query()->whereIn(
            'id', OrganizationProductCategory::query()->whereIn(
                'product_category_id',
                $request->input('product_categories', [])
            )->pluck('organization_id')->toArray()
        )->get();

        resolve('forus.services.mail_notification')->newFundCreated(
            $organization->identity_address,
            $fund->name,
            env('WEB_SHOP_GENERAL_URL')
        );

        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            resolve('forus.services.mail_notification')->newFundApplicable(
                $organization->identity_address,
                $fund->name,
                config('forus.front_ends.panel-provider')
            );
        }


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

        $fund->update($request->only([
            'name', 'state', 'start_date', 'end_date'
        ]));

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

        $dates = $dates->map(function (Carbon $date, $key) use ($fund, $dates) {
            return [
                "key" => $date->format('Y-m-d'),
                "value" => $key == 0 ? 0 : $fund->voucher_transactions()->whereBetween(
                    'voucher_transactions.created_at', [
                        $dates[$key - 1]->copy()->endOfDay(),
                        $date->copy()->endOfDay()
                    ]
                )->sum('voucher_transactions.amount')
            ];
        });

        return [
            'dates' => $dates,
            'usage' => $dates->sum('value'),
            'activations' => $fund->vouchers()->whereNull(
                'parent_id'
            )->whereBetween('created_at', [
                $startDate, $endDate
            ])->count(),
            'providers' => $fund->voucher_transactions()->whereBetween(
                'voucher_transactions.created_at', [
                $startDate, $endDate
            ])->distinct()->groupBy('organization_id')->count()
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

        do {
            $code = strtoupper(
                app()->make('token_generator')->generate(4,4)
            );
        } while(FundTopUp::query()->where('code', $code)->count() > 0);

        return new TopUpResource($fund->top_ups()->create([
            'code'      => $code,
            'state'     => 'pending'
        ]));
    }
}
