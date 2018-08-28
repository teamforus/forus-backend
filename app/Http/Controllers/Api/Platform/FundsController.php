<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\FundResource;
use App\Http\Resources\VoucherResource;
use App\Models\Fund;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('index', Fund::class);

        return FundResource::collection(Fund::getModel()->where(
            'state', 'active'
        )->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @return FundResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Fund $fund)
    {
        $this->authorize('show', $fund);

        if ($fund->state != 'active') {
            return abort(404);
        }

        return new FundResource($fund);
    }

    /**
     * @param Request $request
     * @param Fund $fund
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply(Request $request, Fund $fund) {
        $identity = $request->get('identity');

        // The same identity can't apply twice to the same fund
        if ($fund->vouchers()->where(
            'identity_address', $identity
        )->count() > 0) {
            return response()->json([
                'message' => trans('validation.fund.already_taken'),
                'key' => 'already_taken'
            ], 403);
        }

        $this->authorize('apply', $fund);

        return new VoucherResource($fund->vouchers()->create([
            'amount' => 300,
            'identity_address' => $request->get('identity'),
            'address' => app()->make('token_generator')->address(),
        ]));
    }
}
