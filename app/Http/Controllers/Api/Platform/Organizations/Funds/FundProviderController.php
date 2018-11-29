<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\FundProvider;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class FundProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('indexSponsor', [FundProvider::class, $organization, $fund]);

        $state = $request->input('state', false);
        $organization_funds = $fund->providers();

        if ($state) {
            $organization_funds->where('state', $state);
        }

        return FundProviderResource::collection(
            $organization_funds->get()
        );
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return FundProviderResource|FundProvider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [$organizationFund, $organization, $fund]);

        return new FundProviderResource($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('updateSponsor', [$organizationFund, $organization, $fund]);

        $state = $request->input('state');

        $organizationFund->update(compact('state'));

        if ($state == 'approved') {
            app()->make('forus.services.mail_notification')->providerApproved(
                $organizationFund->organization->identity_address,
                $organizationFund->fund->name,
                $organizationFund->organization->name,
                $organizationFund->fund->organization->name
            );
        } elseif ($state == 'declined') {
            /*app()->make('forus.services.mail_notification')->providerRejected(
                $organizationFund->organization->identity_address,
                $organizationFund->fund->name,
                $organizationFund->organization->name,
                $organizationFund->fund->organization->name
            );*/
        }

        return new FundProviderResource($organizationFund);
    }

    /**
     * @param FinanceRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function finances(
        FinanceRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [$organizationFund, $organization, $fund]);

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
            $firstTransaction = $fund->voucher_transactions()->where([
                'organization_id' => $organizationFund->organization_id
            ])->orderBy(
                'created_at'
            )->first();

            $startDate = $firstTransaction ? $firstTransaction->created_at->subDay() : Carbon::now();
            $endDate = Carbon::now();

            $dates = $rangeBetween($startDate, $endDate, 8);
        } else {
            abort(403, "");
            exit();
        }

        $dates = $dates->map(function (Carbon $date, $key) use ($fund, $dates, $organizationFund) {
            return [
                "key" => $date->format('Y-m-d'),
                "value" => $key == 0 ? 0 : $fund->voucher_transactions()->whereBetween(
                    'voucher_transactions.created_at', [
                        $dates[$key - 1]->copy()->endOfDay(),
                        $date->copy()->endOfDay()
                    ]
                )->where([
                    'organization_id' => $organizationFund->organization_id
                ])->sum('voucher_transactions.amount')
            ];
        });

        $transactions = $fund->voucher_transactions()->whereBetween(
            'voucher_transactions.created_at', [
                $startDate->copy()->endOfDay(),
                $endDate->copy()->endOfDay()
            ]
        )->where([
            'organization_id' => $organizationFund->organization_id
        ])->count();

        $avg_transaction = $dates->where('value', '>', 0)->avg('value');

        $fundUsageInRange = $fund->voucher_transactions()->whereBetween(
            'voucher_transactions.created_at', [
                $startDate->copy()->endOfDay(),
                $endDate->copy()->endOfDay()
            ]
        )->where([
            'organization_id' => $organizationFund->organization_id
        ])->sum('voucher_transactions.amount');

        $fundUsageTotal = $fund->voucher_transactions()->where([
            'organization_id' => $organizationFund->organization_id
        ])->sum('voucher_transactions.amount');

        $providerUsageInRange = $dates->sum('value');
        $providerUsageTotal = $fund->voucher_transactions()->where([
            'organization_id' => $organizationFund->organization_id
        ])->sum('voucher_transactions.amount');

        return [
            'dates' => $dates,
            'usage' => $providerUsageInRange,
            'transactions' => $transactions,
            'avg_transaction' => $avg_transaction ? $avg_transaction : 0,
            'share_in_range' => $fundUsageInRange > 0 ? $providerUsageInRange / $fundUsageInRange : 0,
            'share_total' => $fundUsageTotal > 0 ? $providerUsageTotal / $fundUsageTotal : 0,
        ];
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function transactions(
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [$organizationFund, $organization, $fund]);

        return SponsorVoucherTransactionResource::collection($fund->voucher_transactions()->where([
            'organization_id' => $organizationFund->organization_id
        ])->get());
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @param VoucherTransaction $transaction
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function transaction(
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund,
        VoucherTransaction $transaction
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [$organizationFund, $organization, $fund]);
        $this->authorize('showSponsor', [$transaction, $organization, $fund]);

        return new SponsorVoucherTransactionResource($transaction);
    }
}
