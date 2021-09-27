<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\FundProvidersExport;
use App\Exports\ProviderFinancesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\IndexProvidersRequest;
use App\Http\Resources\ProviderFinancialResource;
use App\Http\Resources\Sponsor\SponsorProviderResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use function foo\func;

/**
 * Class ProvidersController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor
 */
class ProvidersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProvidersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $query = OrganizationQuery::sortByParameter(
            Organization::query(),
            FundProvider::search($request, $organization)->pluck('organization_id')->toArray(),
            $request->input('sort_by', 'created_at')
        );

        return SponsorProviderResource::collection(OrganizationQuery::whereIsProviderOrganization(
            $query, $organization
        )->with(SponsorProviderResource::WITH)->withCount('products')->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Organization $provider
     * @return SponsorProviderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Organization $provider
    ): SponsorProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('viewSponsorProvider', [$organization, $provider]);

        return (new SponsorProviderResource($provider->load(
            SponsorProviderResource::WITH
        )))->additional([
            'sponsor_organization' => $organization,
        ]);
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexProvidersRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $type = $request->input('export_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $exportData = new FundProvidersExport($request, $organization);

        return resolve('excel')->download($exportData, $fileName);
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function finances(
        IndexProvidersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $from = $request->input('from');
        $to = $request->input('to');

        $providers = Organization::searchProviderOrganizations($organization, array_merge($request->only([
            'product_category_ids', 'provider_ids', 'postcodes', 'fund_ids'
        ]), [
            'date_from' => $from ? Carbon::parse($from)->startOfDay() : null,
            'date_to' => $from ? Carbon::parse($to)->endOfDay() : null,
        ]))->with(ProviderFinancialResource::$load);

        return ProviderFinancialResource::collection($providers->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportFinances(
        IndexProvidersRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $from = $request->input('from');
        $to = $request->input('to');

        $providers = Organization::searchProviderOrganizations($organization, array_merge($request->only([
            'product_category_ids', 'provider_ids', 'postcodes', 'fund_ids'
        ]), [
            'date_from' => $from ? Carbon::parse($from)->startOfDay() : null,
            'date_to' => $from ? Carbon::parse($to)->endOfDay() : null,
        ]))->with(ProviderFinancialResource::$load)->get();

        $type = $request->input('export_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $fileData = new ProviderFinancesExport($organization, $providers);

        return resolve('excel')->download($fileData, $fileName);
    }
}
