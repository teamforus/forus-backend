<?php

namespace App\Http\Controllers\Api\Platform\Validator;

use App\Events\Vouchers\VoucherCreated;
use App\Http\Requests\Api\Platform\Validator\ValidatorRequest\ValidateValidatorRequestRequest;
use App\Http\Resources\Validator\ValidatorRequestResource;
use App\Models\ProductRequest;
use App\Models\ValidatorRequest;
use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ValidatorRequestController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Display a listing of the resource.$organization
     *
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request
    ) {
        $this->authorize('index', ValidatorRequest::class);

        return ValidatorRequestResource::collection(
            ValidatorRequest::searchPaginate($request)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param ValidatorRequest $validatorRequest
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        ValidatorRequest $validatorRequest
    ) {
        $this->authorize('show', $validatorRequest);

        return new ValidatorRequestResource($validatorRequest);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ValidateValidatorRequestRequest $request
     * @param ValidatorRequest $validatorRequest
     * @return ValidatorRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        ValidateValidatorRequestRequest $request,
        ValidatorRequest $validatorRequest
    ) {
        $this->authorize('validate', $validatorRequest);

        $state = $request->input('state', false);
        $organization_id = $request->input('organization_id', null);

        if (in_array($state, ['approved', 'declined'])) {
            $validationRequest = $this->recordRepo->makeValidationRequest(
                $validatorRequest->identity_address,
                $validatorRequest->record_id
            );

            if ($state == 'approved') {
                $this->recordRepo->approveValidationRequest(
                    auth_address(),
                    $validationRequest['uuid'],
                    $organization_id
                );
            } else {
                $this->recordRepo->declineValidationRequest(
                    auth_address(),
                    $validationRequest['uuid']
                );
            }

            $validatorRequest->update([
                'state' => $state,
                'record_validation_uid' => $validationRequest['uuid']
            ]);

            if ($state == 'approved' && $validatorRequest->product_request &&
                $validatorRequest->product_request->fund) {
                $productRequest = $validatorRequest->product_request;

                if (!$productRequest->resolved_at &&
                    $productRequest->validator_requests()->where('state', '!=', 'approved')->count() == 0) {
                    /** @var Voucher $regularVoucher */
                    $regularVoucher = $productRequest->fund->makeVoucher($validatorRequest->identity_address);
                    $voucherExpireAt = $regularVoucher->fund->end_date->gt(
                        $regularVoucher->expire_at
                    ) ? $productRequest->product->expire_at : $regularVoucher->fund->end_date;

                    $voucher = Voucher::create([
                        'identity_address'  => $regularVoucher->identity_address,
                        'parent_id'         => $regularVoucher->id,
                        'fund_id'           => $regularVoucher->fund_id,
                        'product_id'        => $productRequest->product->id,
                        'amount'            => $productRequest->product->price,
                        'expire_at'         => $voucherExpireAt
                    ]);

                    VoucherCreated::dispatch($voucher);
                }
            }
        }

        return new ValidatorRequestResource($validatorRequest);
    }
}
