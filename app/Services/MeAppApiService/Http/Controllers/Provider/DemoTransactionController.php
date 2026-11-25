<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider;

use App\Http\Controllers\Api\Platform\Vouchers\DemoTransactionController as LegacyDemoTransactionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Demo\UpdateTransactionRequest;
use App\Models\DemoTransaction;
use App\Services\MeAppApiService\Http\Resources\Provider\DemoTransactionResource;

class DemoTransactionController extends Controller
{
    /**
     * Update a demo payment's state.
     *
     * @param UpdateTransactionRequest $request
     * @param DemoTransaction $transaction
     * @return DemoTransactionResource
     */
    public function update(UpdateTransactionRequest $request, DemoTransaction $transaction): DemoTransactionResource
    {
        $response = resolve(LegacyDemoTransactionController::class)->update($request, $transaction);

        return new DemoTransactionResource($response->resource);
    }
}
