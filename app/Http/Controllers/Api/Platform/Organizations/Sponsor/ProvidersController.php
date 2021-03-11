<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\FundProvidersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\IndexProvidersRequest;
use App\Http\Resources\Sponsor\SponsorProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;
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

        $fileName = date('Y-m-d H:i:s') . '.xls';
        $exportData = new FundProvidersExport($request, $organization);

        return resolve('excel')->download($exportData, $fileName);
    }
}
