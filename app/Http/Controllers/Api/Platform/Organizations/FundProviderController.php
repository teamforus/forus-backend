<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\FundProvidersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Provider\IndexFundProviderRequest;
use App\Http\Resources\FundProviderResource;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FundProviderController extends Controller
{
    /**
     * Show organization providers.
     *
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundProviderRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);

        return FundProviderResource::queryCollection(FundProvider::search($request, $organization), $request);
    }

    /**
     * @param IndexFundProviderRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(
        IndexFundProviderRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [FundProvider::class, $organization]);

        $type = $request->input('export_type', 'xls');

        return resolve('excel')->download(
            new FundProvidersExport($request, $organization),
            date('Y-m-d H:i:s') . '.' . $type
        );
    }
}
