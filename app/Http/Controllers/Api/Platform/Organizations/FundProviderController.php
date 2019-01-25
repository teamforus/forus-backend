<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\FundProvidersExport;
use App\Http\Requests\Api\Platform\Organizations\Provider\IndexFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class FundProviderController extends Controller
{
    /**
     * Show organization providers.
     *
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexSponsor', [FundProvider::class, $organization]);

        return FundProviderResource::collection(
            FundProvider::search($request, $organization)->with(
                FundProviderResource::$load
            )->paginate(
                $request->input('per_page', 10)
            )
        );
    }

    /**
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexFundProviderRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexSponsor', [FundProvider::class, $organization]);

        return resolve('excel')->download(
            new FundProvidersExport($request, $organization),
            date('Y-m-d H:i:s') . '.xls'
        );
    }
}
