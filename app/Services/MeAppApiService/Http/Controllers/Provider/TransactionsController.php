<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider;

use App\Http\Controllers\Api\Platform\Provider\TransactionsController as LegacyTransactionsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource as LegacyTransactionResource;
use App\Models\VoucherTransaction;
use App\Services\MeAppApiService\Http\Resources\Provider\ProviderVoucherTransactionEmployeeResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionsController extends Controller
{
    /**
     * List your provider transactions.
     *
     * @param IndexTransactionsRequest $request
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(IndexTransactionsRequest $request): AnonymousResourceCollection
    {
        $response = resolve(LegacyTransactionsController::class)->index($request);
        $paginator = $response->resource;

        $paginator->setCollection($response->collection->map(
            static fn (LegacyTransactionResource $resource): VoucherTransaction => $resource->resource,
        ));

        return ProviderVoucherTransactionEmployeeResource::collection($paginator);
    }
}
