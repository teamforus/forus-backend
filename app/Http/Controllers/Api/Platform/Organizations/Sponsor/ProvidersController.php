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
        $this->authorize('listSponsorProviders', $organization);
        $query = Organization::searchQuery($request);

        $query->whereHas('fund_providers', function(Builder $builder) use ($request, $organization) {
            FundProvider::search($request, $organization, $builder);
        });

        if ($fund_id = $request->input('fund_id')) {
            $query->whereHas('fund_providers', function(Builder $builder) use ($organization, $fund_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($organization, $fund_id) {
                    $builder->where('organization_id', $organization->id);
                })->where(compact('fund_id'));
            });
        }

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
        $this->authorize('listSponsorProviders', $organization);
        $query = Organization::searchQuery($request);

        if ($fund_id = $request->input('fund_id')) {
            $query->whereHas('fund_providers', function(Builder $builder) use ($organization, $fund_id) {
                $builder->whereHas('fund', function(Builder $builder) use ($organization, $fund_id) {
                    $builder->where('organization_id', $organization->id);
                })->where(compact('fund_id'));
            });
        }

        /** @var Builder $fundProvidersQuery */
        $providers = OrganizationQuery::whereIsProviderOrganization($query, $organization);
        $fundProvidersQuery = FundProvider::whereIn('organization_id', $providers->pluck('id'));
        $fundProvidersQuery = FundProvider::search($request, $organization, $fundProvidersQuery);
        $fileName = date('Y-m-d H:i:s') . '.xls';

        return resolve('excel')->download(
            new FundProvidersExport($request, $organization, $fundProvidersQuery),
            $fileName
        );
    }
}
