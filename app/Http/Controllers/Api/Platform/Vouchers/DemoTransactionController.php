<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Demo\StoreTransactionRequest;
use App\Http\Requests\Api\Platform\Demo\UpdateTransactionRequest;
use App\Http\Resources\DemoTransactionResource;
use App\Models\DemoTransaction;

class DemoTransactionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTransactionRequest $request
     * @return DemoTransactionResource
     */
    public function store(StoreTransactionRequest $request): DemoTransactionResource
    {
        return DemoTransactionResource::create(DemoTransaction::create([
            'token' => app('token_generator')->generate(16),
            'state' => 'pending'
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param DemoTransaction $transaction
     * @return DemoTransactionResource
     */
    public function show(DemoTransaction $transaction): DemoTransactionResource
    {
        return DemoTransactionResource::create($transaction);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTransactionRequest $request
     * @param DemoTransaction $transaction
     * @return DemoTransactionResource
     */
    public function update(
        UpdateTransactionRequest $request,
        DemoTransaction $transaction
    ): DemoTransactionResource {
        return DemoTransactionResource::create($transaction->updateModel([
            'state' => $request->get('state')
        ]));
    }
}
