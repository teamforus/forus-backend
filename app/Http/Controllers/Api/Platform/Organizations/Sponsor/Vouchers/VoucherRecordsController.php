<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Vouchers;

use App\Events\VoucherRecords\VoucherRecordDeleted;
use App\Events\VoucherRecords\VoucherRecordUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords\IndexVoucherRecordRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords\StoreVoucherRecordRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Vouchers\VoucherRecords\UpdateVoucherRecordRequest;
use App\Http\Resources\Sponsor\SponsorVoucherRecordResource;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Searches\VoucherRecordSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Event;

class VoucherRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexVoucherRecordRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        IndexVoucherRecordRequest $request,
        Organization $organization,
        Voucher $voucher
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [VoucherRecord::class, $voucher, $organization]);

        $search = new VoucherRecordSearch($request->only([
            'q', 'order_by', 'order_dir',
        ]), $voucher->voucher_records());

        return SponsorVoucherRecordResource::queryCollection($search->query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherRecordRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherRecordResource
     * @throws AuthorizationException
     */
    public function store(
        StoreVoucherRecordRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherRecordResource {
        $this->authorize('create', [VoucherRecord::class, $voucher, $organization]);

        return new SponsorVoucherRecordResource($voucher->appendRecord(
            $request->string('record_type_key'),
            $request->string('value'),
            $request->string('note'),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return SponsorVoucherRecordResource
     * @throws AuthorizationException
     */
    public function show(
        Organization $organization,
        Voucher $voucher,
        VoucherRecord $voucherRecord
    ): SponsorVoucherRecordResource {
        $this->authorize('view', [$voucherRecord, $voucher, $organization]);

        return new SponsorVoucherRecordResource($voucherRecord);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateVoucherRecordRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return SponsorVoucherRecordResource
     * @throws AuthorizationException
     */
    public function update(
        UpdateVoucherRecordRequest $request,
        Organization $organization,
        Voucher $voucher,
        VoucherRecord $voucherRecord
    ): SponsorVoucherRecordResource {
        $this->authorize('update', [$voucherRecord, $voucher, $organization]);

        $voucherRecord->update($request->only([
            'value', 'note',
        ]));

        Event::dispatch(new VoucherRecordUpdated($voucherRecord));

        return new SponsorVoucherRecordResource($voucherRecord);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @param VoucherRecord $voucherRecord
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(
        Organization $organization,
        Voucher $voucher,
        VoucherRecord $voucherRecord
    ): JsonResponse {
        $this->authorize('delete', [$voucherRecord, $voucher, $organization]);

        $voucherRecord->delete();
        Event::dispatch(new VoucherRecordDeleted($voucherRecord));

        return new JsonResponse();
    }
}
