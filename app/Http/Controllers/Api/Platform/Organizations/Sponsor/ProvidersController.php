<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\FundProvidersExport;
use App\Exports\ProviderFinancesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\IndexProvidersRequest;
use App\Http\Resources\Sponsor\SponsorProviderResource;
use App\Models\FundProvider;
use App\Models\Office;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $query = Organization::query();
        $query->whereHas('fund_providers', function(Builder $builder) use ($request, $organization) {
            FundProvider::search($request, $organization, $builder);
        });

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
     * @param Organization $organization
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostcodes(
        IndexProvidersRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $query = Organization::query();
        $query->whereHas('fund_providers', function(Builder $builder) use ($request, $organization) {
            FundProvider::search($request, $organization, $builder);
        });

        $postcodes = [];
        OrganizationQuery::whereIsProviderOrganization(
            $query, $organization
        )->each(function (Organization $organization) use (&$postcodes) {
            $organization->offices->each(function (Office $office) use (&$postcodes) {
                if ($office->postal_code && !in_array($office->postal_code, $postcodes)) {
                    $postcodes[] = $office->postal_code;
                }
            });
        });

        $response = [];
        foreach($postcodes as $index => $postcode) {
            $response[] = [
                'id'   => $index,
                'name' => $postcode
            ];
        }

        return response()->json($response);
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     */
    public function getFinancesTotals(
        IndexProvidersRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $providerOrganizations = Organization::getProviderOrganizations(
            $request, $organization
        )->get();
        $fundProviders = FundProvider::getFundProviders($providerOrganizations);

        return response()->json(
            FundProvider::getFundProviderTotals($fundProviders, $providerOrganizations)
        );
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getFinancesPerProvider(
        IndexProvidersRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $providerOrganizations = Organization::getProviderOrganizations(
            $request, $organization
        )->get();

        return response()->json(
            FundProvider::getTotalsPerFundProvider($providerOrganizations)
        );
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
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $type = $request->input('export_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $exportData = new ProviderFinancesExport($request, $organization);

        return resolve('excel')->download($exportData, $fileName);
    }
}
