<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Requests\Api\Platform\Organizations\Vouchers\AssignVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\SendVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreBatchVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreVoucherRequest;
use App\Http\Resources\Sponsor\SponsorVoucherResource;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Voucher;
use App\Http\Controllers\Controller;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class VouchersController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor
 */
class VouchersController extends Controller
{
    protected $identityRepo;
    protected $recordRepo;

    /**
     * VouchersController constructor.
     * @param IIdentityRepo $identityRepo
     * @param IRecordRepo $recordRepo
     */
    public function __construct(
        IIdentityRepo $identityRepo,
        IRecordRepo $recordRepo
    ) {
        $this->identityRepo = $identityRepo;
        $this->recordRepo = $recordRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexVouchersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        return SponsorVoucherResource::collection(Voucher::searchSponsorQuery(
            $request,
            $organization,
            Fund::find($request->get('fund_id'))
        )->paginate($request->input('per_page', 25)));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     * @return SponsorVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function store(
        StoreVoucherRequest $request,
        Organization $organization
    ): SponsorVoucherResource {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        $note       = $request->input('note', null);
        $email      = $request->input('email', false);
        $amount     = $fund->isTypeBudget() ? $request->input('amount', 0) : 0;
        $identity   = $email ? $this->identityRepo->getOrMakeByEmail($email) : null;
        $expire_at  = $request->input('expire_at', false);
        $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
        $product_id = $request->input('product_id');

        if ($product_id && $fund->isTypeBudget()) {
            $voucher = $fund->makeProductVoucher($identity, $product_id, $expire_at, $note);
        } else {
            $voucher = $fund->makeVoucher($identity, $amount, $expire_at, $note);
        }

        if ($activation_code = $request->input('activation_code', false)) {
            Prevalidation::deactivateByUid($activation_code);
        }

        return new SponsorVoucherResource($voucher->updateModel([
            'employee_id' => Employee::getEmployee($request->auth_address())->id
        ]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBatchVoucherRequest $request
     * @param Organization $organization
     * @return SponsorVoucherResource|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeBatch(
        StoreBatchVoucherRequest $request,
        Organization $organization
    ) {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        return SponsorVoucherResource::collection(collect(
            $request->post('vouchers')
        )->map(function($voucher) use ($fund,  $organization, $request) {
            $note       = $voucher['note'] ?? null;
            $email      = $voucher['email'] ?? false;
            $amount     = $fund->isTypeBudget() ? $voucher['amount'] ?? 0 : 0;
            $identity   = $email ? $this->identityRepo->getOrMakeByEmail($email) : null;
            $expire_at  = $voucher['expire_at'] ?? false;
            $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
            $product_id = $voucher['product_id'] ?? false;

            if (!$product_id || !$fund->isTypeBudget()) {
                $voucher = $fund->makeVoucher($identity, $amount, $expire_at, $note);
            } else {
                $voucher = $fund->makeProductVoucher($identity, $product_id, $expire_at, $note);
            }

            return $voucher->updateModel([
                'employee_id' => $organization->findEmployee($request->auth_address())->id
            ]);
        }));
    }

    /**
     * Validate store a newly created resource in storage.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     */
    public function storeValidate(
        StoreVoucherRequest $request,
        Organization $organization
    ): void {}

    /**
     * Validate store a newly created resource in storage.
     *
     * @param StoreBatchVoucherRequest $request
     * @param Organization $organization
     */
    public function storeBatchValidate(
        StoreBatchVoucherRequest $request,
        Organization $organization
    ): void {}

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param AssignVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function assign(
        AssignVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('assignSponsor', [$voucher, $organization]);

        return new SponsorVoucherResource($voucher->assignToIdentity(
            $this->identityRepo->getOrMakeByEmail($request->post('email'))
        ));
    }

    /**
     *
     * @param SendVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sendByEmail(
        SendVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('sendByEmailSponsor', [$voucher, $organization]);

        $voucher->sendToEmail($request->post('email'));

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function exportUnassigned(
        IndexVouchersRequest $request,
        Organization $organization
    ): BinaryFileResponse {

        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        $fund = Fund::findOrFail($request->get('fund_id'));
        $this->authorize('viewAnySponsor', [Voucher::class, $fund->organization]);

        $export_type = $request->get('export_type', 'png');
        $unassigned_vouchers = Voucher::searchSponsor($request, $organization, $fund);

        if ($unassigned_vouchers->count() === 0) {
            abort(404, "No unassigned vouchers to be exported.");
        }

        if (!$zipFile = Voucher::zipVouchers($unassigned_vouchers, $export_type)) {
            abort(500, "Couldn't make the archive.");
        }

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }
}
