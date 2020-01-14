<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\FundRequestsExport;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Resources\FundRequestResource;
use App\Models\FundRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestsRequest $request,
        Organization $organization
    ) {
        $this->authorize('viewAnyValidator', [
            FundRequest::class, $organization->funds[0], $organization
        ]);

        return FundRequestResource::collection(
            FundRequest::search($request, $organization)->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexFundRequestsRequest $request,
        Organization $organization
    ) {
        $this->authorize('index', Organization::class);

        return resolve('excel')->download(
            new FundRequestsExport($request, $organization),
            date('Y-m-d H:i:s') . '.xls'
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest
    ) {
        $this->authorize('viewRequester', [
            $fundRequest, $fundRequest->fund, $organization
        ]);

        return new FundRequestResource($fundRequest);
    }
}
