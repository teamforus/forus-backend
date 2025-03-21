<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\FundProvidersExport;
use App\Exports\ProviderFinancesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\IndexProvidersRequest;
use App\Http\Resources\ProviderFinancialResource;
use App\Http\Resources\Sponsor\SponsorProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProvidersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProvidersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $query = OrganizationQuery::whereIsProviderOrganization(Organization::query(), $organization);

        $query = $query->whereHas('fund_providers', function (Builder $builder) use ($request, $organization) {
            FundProvider::search($request, $organization, $builder);
        });

        $query = OrganizationQuery::whereGroupState(
            $query,
            $organization,
            $request->get('state_group'),
        );

        $query = OrganizationQuery::orderProvidersBy($query, $organization, $request->only([
            'order_by', 'order_dir',
        ]));

        return SponsorProviderResource::queryCollection($query, $request, [
            'resource_type' => $request->input('resource_type', 'default'),
            'sponsor_organization' => $organization,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Organization $provider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorProviderResource
     */
    public function show(
        Organization $organization,
        Organization $provider
    ): SponsorProviderResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('viewSponsorProvider', [$organization, $provider]);

        return SponsorProviderResource::create($provider, [
            'sponsor_organization' => $organization,
        ]);
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(
        IndexProvidersRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);
        $this->authorize('listSponsorProviders', $organization);

        $type = $request->input('export_type', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $exportData = new FundProvidersExport($request, $organization);

        return resolve('excel')->download($exportData, $fileName);
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
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
            'product_category_ids', 'provider_ids', 'postcodes', 'fund_ids', 'business_type_ids',
        ]), [
            'date_from' => $from ? Carbon::parse($from)->startOfDay() : null,
            'date_to' => $to ? Carbon::parse($to)->endOfDay() : null,
        ]))->with(ProviderFinancialResource::$load);

        return ProviderFinancialResource::collection($providers->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * @param IndexProvidersRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
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
            'product_category_ids', 'provider_ids', 'postcodes', 'fund_ids', 'business_type_ids',
        ]), [
            'date_from' => $from ? Carbon::parse($from)->startOfDay() : null,
            'date_to' => $to ? Carbon::parse($to)->endOfDay() : null,
        ]))->with(ProviderFinancialResource::$load)->get();

        $type = $request->input('export_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $fileData = new ProviderFinancesExport($organization, $providers);

        return resolve('excel')->download($fileData, $fileName);
    }
}
