<?php

namespace App\Http\Controllers\Api\Platform\Funds;

use App\Events\FundRequests\FundRequestCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestValidationRequest;
use App\Http\Resources\FundRequestResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Services\IConnectApiService\Exceptions\PersonBsnApiException;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Throwable;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequestsRequest $request,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsRequester', [FundRequest::class, $fund]);

        return FundRequestResource::queryCollection($fund->fund_requests()->where([
            'identity_id' => $request->auth_id(),
        ]), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequestRequest $request
     * @param Fund $fund
     * @throws Throwable
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestResource|JsonResponse
     */
    public function store(
        StoreFundRequestRequest $request,
        Fund $fund,
    ): FundRequestResource|JsonResponse {
        $this->authorize('check', $fund);
        $this->authorize('createAsRequester', [FundRequest::class, $fund]);

        DB::beginTransaction();

        try {
            $fundRequest = $fund->makeFundRequest(
                $request->identity(),
                $request->input('records'),
                $request->input('contact_information')
            );

            if ($type = $fund->fund_config->getApplicationPhysicalCardRequestType()) {
                $fundRequest->makePhysicalCardRequest([
                    'address' => $request->input('physical_card_request_address.street'),
                    'house' => $request->input('physical_card_request_address.house_nr'),
                    'house_addition' => $request->input('physical_card_request_address.house_nr_addition'),
                    'postcode' => $request->input('physical_card_request_address.postal_code'),
                    'city' => $request->input('physical_card_request_address.city'),
                    'physical_card_type_id' => $type->id,
                ]);
            }
        } catch (PersonBsnApiException $e) {
            DB::rollBack();

            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }

        DB::commit();

        $responseData = [];

        if (Gate::forUser($request->identity())->allows('viewPersonBsnApiRecords', $fund)) {
            $fundPrefills = IConnectPrefill::getBsnApiPrefills($fund, $request->identity()->bsn, true);
            $responseData = Arr::get($fundPrefills, 'response');
        }

        Event::dispatch(new FundRequestCreated($fundRequest, responseData: $responseData));

        return FundRequestResource::create($fundRequest);
    }

    /**
     * @param StoreFundRequestValidationRequest $request
     * @param Fund $fund
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StoreFundRequestValidationRequest $request,
        Fund $fund
    ): void {
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestResource
     */
    public function show(Fund $fund, FundRequest $fundRequest): FundRequestResource
    {
        $this->authorize('viewAsRequester', [$fundRequest, $fund]);

        return FundRequestResource::create($fundRequest);
    }
}
