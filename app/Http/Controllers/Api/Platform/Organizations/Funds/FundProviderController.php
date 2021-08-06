<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Exports\VoucherTransactionsSponsorExport;
use App\Http\Requests\Api\Platform\Organizations\Funds\FinanceRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\IndexFundProviderRequest;
use App\Http\Requests\Api\Platform\Organizations\Funds\UpdateFundProviderRequest;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\FundProviderResource;
use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\FundProvider;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class FundProviderController
 * @package App\Http\Controllers\Api\Platform\Organizations\Funds
 */
class FundProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization, $fund]);

        $query = $fund->providers()->getQuery();
        $state = $request->input('state', false);

        if ($request->input('q')) {
            $query = FundProviderQuery::queryFilter($query, $request->input('q'));
        }

        if ($request->input('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($state === FundProvider::STATE_APPROVED) {
            $query = FundProviderQuery::whereApprovedForFundsFilter($query, $fund->id);
        }

        if ($state === FundProvider::STATE_PENDING) {
            $query = FundProviderQuery::wherePendingForFundsFilter($query, $fund->id);
        }

        if ($state === FundProvider::STATE_APPROVED_OR_HAS_TRANSACTIONS) {
            $query->where(static function(Builder $builder) use ($fund) {
                FundProviderQuery::whereApprovedForFundsFilter($builder, $fund->id);

                $builder->orWhere(function(Builder $builder) use ($fund) {
                    FundProviderQuery::whereHasTransactions($builder, $fund->id);
                });
            });
        }

        return FundProviderResource::collection(
            FundProviderQuery::sortByRevenue($query, $fund->id)->paginate(
                $request->input('per_page')
            )
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
    ): FundProviderResource {
        $this->authorize('showSponsor', [
            $organizationFund, $organization, $fund
        ]);

        return new FundProviderResource($organizationFund);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundProviderRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @return FundProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundProviderRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ): FundProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('updateSponsor', [$fundProvider, $organization, $fund]);

        $enable_products = $request->input('enable_products');
        $disable_products = $request->input('disable_products');

        $fundProvider->update($request->only($fund->isTypeBudget() ? [
            'dismissed', 'allow_products', 'allow_budget',
        ]: 'dismissed'));

        if ($fundProvider->allow_budget || $fundProvider->allow_products || !empty($enable_products)) {
            $fundProvider->update([
                'dismissed' => false
            ]);
        }

        if ($request->input('dismissed')) {
            $disable_products = $fundProvider->products->pluck('id')->toArray();
        }

        if (is_array($enable_products)) {
            $fundProvider->approveProducts($enable_products);
        }

        if (is_array($disable_products)) {
            $fundProvider->declineProducts($disable_products);
        }

        $fundProvider->update([
            'allow_some_products' => $fundProvider->products()->count() > 0
        ]);

        return new FundProviderResource($fundProvider);
    }

    /**
     * Delete fund provider
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('deleteSponsor', [$fundProvider, $organization, $fund]);

        $fundProvider->delete();

        return response()->json([]);
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
    ): array {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [
            $organizationFund, $organization, $fund
        ]);

        return $organizationFund->getFinances($request, $fund);
    }

    /**
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function transactions(
        IndexTransactionsRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [
            $organizationFund, $organization, $fund
        ]);

        return SponsorVoucherTransactionResource::collection(
            VoucherTransaction::searchSponsor(
                $request,
                $organization,
                $fund,
                $organizationFund->organization
            )->paginate()
        );
    }

    /**
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $organizationFund
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function transactionsExport(
        IndexTransactionsRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $organizationFund
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [
            $organizationFund, $organization, $fund
        ]);

        return resolve('excel')->download(
            new VoucherTransactionsSponsorExport(
                $request,
                $organization,
                $fund,
                $organizationFund->organization
            ), date('Y-m-d H:i:s') . '.xls'
        );
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
    ): SponsorVoucherTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);
        $this->authorize('showSponsor', [
            $organizationFund, $organization, $fund
        ]);
        $this->authorize('showSponsor', [
            $transaction, $organization, $fund
        ]);

        return new SponsorVoucherTransactionResource($transaction);
    }
}
