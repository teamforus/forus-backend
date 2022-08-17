<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Events\Funds\FundVouchersExportedEvent;
use App\Exports\VoucherExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\ActivateVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\ActivationCodeVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\AssignVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\DeactivateVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\IndexVouchersExportFieldsRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\SendVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreBatchVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreVoucherRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Arr\VoucherExportArrResource;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\UpdateVoucherRequest;
use App\Http\Resources\Sponsor\SponsorVoucherResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\Identity;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

/**
 * Class VouchersController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor
 */
class VouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function index(
        IndexVouchersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        return SponsorVoucherResource::collection(Voucher::searchSponsorQuery(
            $request, $organization, $organization->findFund($request->get('fund_id'))
        )->paginate($request->input('per_page', 25)));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     * @return SponsorVoucherResource
     * @throws AuthorizationException|Exception
     * @noinspection PhpUnused
     */
    public function store(
        StoreVoucherRequest $request,
        Organization $organization
    ): SponsorVoucherResource {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        $note       = $request->input('note');
        $email      = $request->input('email', false);
        $amount     = $fund->isTypeBudget() ? $request->input('amount', 0) : 0;
        $identity   = $email ? Identity::findOrMake($email)->address : null;
        $expire_at  = $request->input('expire_at', false);
        $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
        $product_id = $request->input('product_id');
        $multiplier = $request->input('limit_multiplier');
        $employee_id = $organization->findEmployee($request->auth_address())->id;
        $extraFields = compact('note', 'employee_id');
        $productVouchers = [];

        if ($product_id) {
            $mainVoucher = $fund->makeProductVoucher($identity, $extraFields, $product_id, $expire_at);
        } else {
            $mainVoucher = $fund->makeVoucher($identity, $extraFields, $amount, $expire_at, $multiplier);
            $productVouchers = $fund->makeFundFormulaProductVouchers($identity, $extraFields, $expire_at);
        }

        /** @var Voucher[] $vouchers */
        $vouchers = array_merge([$mainVoucher], $productVouchers);

        foreach ($vouchers as $voucher) {
            if ($organization->bsn_enabled && ($bsn = $request->input('bsn', false))) {
                $voucher->setBsnRelation($bsn)->assignByBsnIfExists();
            }

            if (!$voucher->is_granted) {
                if (!$request->input('activate')) {
                    $voucher->setPending();
                }

                if ($request->input('activation_code')) {
                    $voucher->makeActivationCode($request->input('activation_code_uid'));
                }
            }
        }

        return new SponsorVoucherResource($mainVoucher);
    }

    /**
     * Validate store a newly created resource in storage.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StoreVoucherRequest $request,
        Organization $organization
    ): void {}

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBatchVoucherRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function storeBatch(
        StoreBatchVoucherRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        return SponsorVoucherResource::collection(collect(
            $request->post('vouchers')
        )->map(function($voucher) use ($fund,  $organization, $request) {
            $note       = $voucher['note'] ?? null;
            $email      = $voucher['email'] ?? false;
            $amount     = $fund->isTypeBudget() ? $voucher['amount'] ?? 0 : 0;
            $identity   = $email ? Identity::findOrMake($email)->address : null;
            $expire_at  = $voucher['expire_at'] ?? false;
            $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
            $product_id = $voucher['product_id'] ?? false;
            $multiplier = $voucher['limit_multiplier'] ?? null;
            $employee_id = $organization->findEmployee($request->auth_address())->id;
            $extraFields = compact('note', 'employee_id');
            $productVouchers = [];

            if ($product_id) {
                $mainVoucher = $fund->makeProductVoucher($identity, $extraFields, $product_id, $expire_at);
            } else {
                $mainVoucher = $fund->makeVoucher($identity, $extraFields, $amount, $expire_at, $multiplier);
                $productVouchers = $fund->makeFundFormulaProductVouchers($identity, $extraFields, $expire_at);
            }

            /** @var Voucher[] $vouchers */
            $vouchers = array_merge([$mainVoucher], $productVouchers);

            foreach ($vouchers as $voucherModel) {
                if ($organization->bsn_enabled && ($bsn = ($voucher['bsn'] ?? false))) {
                    $voucherModel->setBsnRelation((string) $bsn)->assignByBsnIfExists();
                }

                if (!$voucherModel->is_granted) {
                    if (!($voucher['activate'] ?? false)) {
                        $voucherModel->setPending();
                    }

                    if ($voucher['activation_code'] ?? false) {
                        $voucherModel->makeActivationCode($voucher['activation_code_uid'] ?? null);
                    }
                }
            }

            return $mainVoucher;
        }));
    }

    /**
     * Validate store a newly created resource in storage.
     *
     * @param StoreBatchVoucherRequest $request
     * @param Organization $organization
     * @noinspection PhpUnused
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
     * @throws AuthorizationException
     * @noinspection PhpUnused
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
     * @throws AuthorizationException|Exception
     * @noinspection PhpUnused
     */
    public function assign(
        AssignVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('assignSponsor', [$voucher, $organization]);

        $bsn = $request->post('bsn');
        $email = $request->post('email');

        if ($email) {
            $voucher->assignToIdentity(Identity::findOrMake($email));
        } else if ($organization->bsn_enabled && $bsn) {
            $voucher->setBsnRelation($bsn)->assignByBsnIfExists();
        }

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param UpdateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws AuthorizationException
     */
    public function update(
        UpdateVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$voucher, $organization]);

        if ($voucher->fund->isTypeSubsidy() && $request->has('limit_multiplier')) {
            $voucher->update($request->only('limit_multiplier'));
        }

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param ActivateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws AuthorizationException|Exception
     * @noinspection PhpUnused
     */
    public function activate(
        ActivateVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('activateSponsor', [$voucher, $organization]);

        $voucher->activateAsSponsor(
            $request->input('note') ?: '',
            $organization->findEmployee($request->auth_address())
        );

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param DeactivateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws AuthorizationException|Exception
     * @noinspection PhpUnused
     */
    public function deactivate(
        DeactivateVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('deactivateSponsor', [$voucher, $organization]);

        $voucher->deactivate(
            $request->input('note') ?: '',
            $request->input('notify_by_email', false),
            $organization->findEmployee($request->auth_address())
        );

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param ActivationCodeVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws AuthorizationException|Exception
     * @noinspection PhpUnused
     */
    public function makeActivationCode(
        ActivationCodeVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('makeActivationCodeSponsor', [$voucher, $organization]);

        $voucher->makeActivationCode($request->input('activation_code_uid'));

        return new SponsorVoucherResource($voucher);
    }

    /**
     *
     * @param SendVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws AuthorizationException
     * @noinspection PhpUnused
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
     * @param IndexVouchersExportFieldsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function getExportFields(
        IndexVouchersExportFieldsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        return ExportFieldArrResource::collection(VoucherExport::getExportFields(
            $request->input('type', 'budget')
        ));
    }

    /**
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return VoucherExportArrResource
     * @throws AuthorizationException|Exception
     */
    public function export(
        IndexVouchersRequest $request,
        Organization $organization
    ): VoucherExportArrResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        $fund = $organization->findFund($request->get('fund_id'));
        $fields = $request->input('fields', VoucherExport::getExportFields('product'));
        $qrFormat = $request->get('qr_format');
        $dataFormat = $request->get('data_format', 'csv');

        $vouchers = Voucher::searchSponsorQuery($request, $organization, $fund)->with([
            'transactions', 'voucher_relation', 'product', 'fund',
            'token_without_confirmation', 'identity.primary_email', 'product_vouchers'
        ])->get();

        $exportData = Voucher::exportData($vouchers, $fields, $dataFormat, $qrFormat);

        FundVouchersExportedEvent::dispatch($fund, [
            'fields' => $fields,
            'qr_format' => $qrFormat,
            'data_format' => $dataFormat,
            'voucher_ids' => $vouchers->pluck('id'),
        ]);

        return new VoucherExportArrResource(Arr::only($exportData, ['files', 'data']));
    }
}
