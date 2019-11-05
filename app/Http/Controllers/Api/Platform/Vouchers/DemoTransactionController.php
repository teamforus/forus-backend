<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Requests\Api\Platform\Demo\StoreTransactionRequest;
use App\Http\Requests\Api\Platform\Demo\UpdateTransactionRequest;
use App\Http\Resources\DemoTransactionResource;
use App\Models\DemoTransaction;
use App\Http\Controllers\Controller;

class DemoTransactionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTransactionRequest $request
     * @return DemoTransactionResource
     */
    public function store(StoreTransactionRequest $request)
    {
        $demo_transaction = DemoTransaction::query()->create([
            'token' => app('token_generator')->generate(18),
            'state' => 'pending'
        ]);
        return new DemoTransactionResource($demo_transaction);
    }
    /**
     * Display the specified resource.
     *
     * @param DemoTransaction $transaction
     * @return mixed
     */
    public function show(DemoTransaction $transaction)
    {
        return new DemoTransactionResource($transaction);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTransactionRequest $request
     * @param DemoTransaction $transaction
     * @return mixed
     */
    public function update(
        UpdateTransactionRequest $request,
        DemoTransaction $transaction
    ) {
        $transaction->update([
            'state' => $request->get('state')
        ]);
        return new DemoTransactionResource($transaction);
    }
}
