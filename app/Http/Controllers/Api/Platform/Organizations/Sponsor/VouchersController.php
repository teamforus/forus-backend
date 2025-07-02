<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Events\Funds\FundVouchersExportedEvent;
use App\Events\Vouchers\VoucherLimitUpdated;
use App\Exports\VoucherExport;
use App\Helpers\Arr;
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
use App\Http\Requests\Api\Platform\Organizations\Vouchers\UpdateVoucherRequest;
use App\Http\Resources\Arr\ExportFieldVoucherArrResource;
use App\Http\Resources\Arr\VoucherExportArrResource;
use App\Http\Resources\Sponsor\SponsorVoucherResource;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherRelation;
use App\Scopes\Builders\VoucherSubQuery;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Event;
use Throwable;

class VouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @throws Exception
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        IndexVouchersRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        return SponsorVoucherResource::queryCollection(Voucher::searchSponsorQuery(
            $request,
            $organization,
            $organization->findFund($request->get('fund_id'))
        ), $request);
    }

    /**
     * Stores a new voucher for the given organization.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     * @throws AuthorizationException|Exception
     * @return SponsorVoucherResource
     */
    public function store(
        StoreVoucherRequest $request,
        Organization $organization,
    ): SponsorVoucherResource {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        $note = $request->input('note');
        $email = $request->input('email', false);
        $amount = currency_format($request->input('amount', 0));
        $expire_at = $request->input('expire_at', false);
        $expire_at = $expire_at ? Carbon::parse($expire_at) : null;
        $product_id = $request->input('product_id');
        $identity = $email ? Identity::findOrMake($email) : null;
        $multiplier = $request->input('limit_multiplier');
        $records = $request->input('records', []);
        $notifyProvider = $request->input('notify_provider', false);

        $bsn = $request->input('bsn', false);
        $report_type = $request->input('report_type', VoucherRelation::REPORT_TYPE_USER);

        $employee_id = $organization->findEmployee($request->auth_address())->id;
        $extraFields = compact('note', 'employee_id');
        $productVouchers = [];
        $allowVoucherRecords = $fund->fund_config?->allow_voucher_records;

        if ($product_id) {
            $mainVoucher = $fund
                ->makeProductVoucher($identity, $extraFields, $product_id, $expire_at, $notifyProvider)
                ->dispatchCreated(notifyRequesterReserved: false, notifyProviderReserved: false, notifyProviderReservedBySponsor: $notifyProvider);
        } else {
            $mainVoucher = $fund
                ->makeVoucher($identity, $extraFields, $amount, $expire_at, $multiplier)
                ->dispatchCreated();

            $productVouchers = $fund->makeFundFormulaProductVouchers($identity, $extraFields, $expire_at);
        }

        /** @var Voucher[] $vouchers */
        $vouchers = array_merge([$mainVoucher], $productVouchers);
        $mainVoucher->appendRecords($allowVoucherRecords ? $records : []);

        foreach ($vouchers as $voucher) {
            if ($organization->bsn_enabled && $bsn) {
                $voucher->setBsnRelation($bsn, $report_type)->assignByBsnIfExists();
            }

            if (!$voucher->granted) {
                if (!$request->input('activate')) {
                    $voucher->setPending();
                }

                if ($request->input('activation_code')) {
                    $voucher->makeActivationCode($request->input('client_uid'));
                }
            }

            if ($client_uid = $request->input('client_uid')) {
                $voucher->update([
                    'client_uid' => $client_uid,
                ]);
            }

            if ($voucher->voucher_relation?->bsn && $voucher->voucher_relation?->isReportByRelation()) {
                $voucher->reportBackofficeReceived(onlyAssigned: false);
            }
        }

        return SponsorVoucherResource::create($mainVoucher);
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
    ): void {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBatchVoucherRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function storeBatch(
        StoreBatchVoucherRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $fund = Fund::find($request->post('fund_id'));
        $file = $request->post('file');
        $employee = $request->employee($organization);
        $vouchers = $request->post('vouchers');
        $allowVoucherRecords = $fund?->fund_config?->allow_voucher_records;

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        $event = $employee->logCsvUpload($employee::EVENT_UPLOADED_VOUCHERS, $file, $vouchers);

        $voucherModels = collect($vouchers)->map(function ($voucher) use (
            $fund,
            $organization,
            $request,
            $employee,
            $allowVoucherRecords,
        ) {
            $note = $voucher['note'] ?? null;
            $email = $voucher['email'] ?? false;
            $amount = currency_format($voucher['amount'] ?? 0);
            $records = isset($voucher['records']) && is_array($voucher['records']) ? $voucher['records'] : [];
            $identity = $email ? Identity::findOrMake($email) : null;
            $expire_at = $voucher['expire_at'] ?? false;
            $expire_at = $expire_at ? Carbon::parse($expire_at) : null;
            $product_id = $voucher['product_id'] ?? false;
            $multiplier = $voucher['limit_multiplier'] ?? null;
            $notifyProvider = $voucher['notify_provider'] ?? false;

            $employee_id = $employee->id;
            $extraFields = compact('note', 'employee_id');
            $payment_iban = $voucher['direct_payment_iban'] ?? null;
            $payment_name = $voucher['direct_payment_name'] ?? null;

            if ($product_id) {
                $mainVoucher = $fund
                    ->makeProductVoucher($identity, $extraFields, $product_id, $expire_at, $notifyProvider)
                    ->dispatchCreated(notifyRequesterReserved: false, notifyProviderReserved: false, notifyProviderReservedBySponsor: $notifyProvider);
            } else {
                $mainVoucher = $fund
                    ->makeVoucher($identity, $extraFields, $amount, $expire_at, $multiplier)
                    ->dispatchCreated();

                $productVouchers = $fund->makeFundFormulaProductVouchers($identity, $extraFields, $expire_at);
            }

            /** @var Voucher[] $vouchers */
            $vouchers = array_merge([$mainVoucher], $productVouchers ?? []);
            $mainVoucher->appendRecords($allowVoucherRecords ? $records : []);

            foreach ($vouchers as $voucherModel) {
                if ($organization->bsn_enabled && ($bsn = ($voucher['bsn'] ?? false))) {
                    $voucherModel
                        ->setBsnRelation((string) $bsn, VoucherRelation::REPORT_TYPE_USER)
                        ->assignByBsnIfExists();
                }

                if (!$voucherModel->granted) {
                    if (!($voucher['activate'] ?? false)) {
                        $voucherModel->setPending();
                    }

                    if ($voucher['activation_code'] ?? false) {
                        $voucherModel->makeActivationCode($voucher['client_uid'] ?? null);
                    }
                }

                if ($client_uid = $voucher['client_uid'] ?? null) {
                    $voucherModel->update([
                        'client_uid' => $client_uid,
                    ]);
                }
            }

            if ($payment_iban && $mainVoucher->isBudgetType() && $fund->generatorDirectPaymentsAllowed()) {
                $mainVoucher->makeDirectPayment($payment_iban, $payment_name, $employee);
            }

            return $mainVoucher;
        });

        $event->forceFill([
            'data->uploaded_file_meta->state' => 'success',
            'data->uploaded_file_meta->created_ids' => $voucherModels->pluck('id')->toArray(),
        ])->update();

        return SponsorVoucherResource::collection($voucherModels);
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
        Organization $organization,
    ): void {
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException
     * @return SponsorVoucherResource
     * @noinspection PhpUnused
     */
    public function show(
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param AssignVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException|Exception
     * @return SponsorVoucherResource
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
        } elseif ($organization->bsn_enabled && $bsn) {
            $voucher->setBsnRelation($bsn, VoucherRelation::REPORT_TYPE_USER)->assignByBsnIfExists();
        }

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param UpdateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException
     * @return SponsorVoucherResource
     */
    public function update(
        UpdateVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$voucher, $organization]);

        $currentLimitMultiplier = $voucher->limit_multiplier;

        if ($request->post('limit_multiplier') != $currentLimitMultiplier) {
            $voucher->update([
                'limit_multiplier' => $request->post('limit_multiplier'),
            ]);

            Event::dispatch(new VoucherLimitUpdated($voucher, $currentLimitMultiplier));
        }

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param ActivateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException|Exception
     * @return SponsorVoucherResource
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

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param DeactivateVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException|Exception|Throwable
     * @return SponsorVoucherResource
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

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param ActivationCodeVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException|Exception
     * @return SponsorVoucherResource
     * @noinspection PhpUnused
     */
    public function makeActivationCode(
        ActivationCodeVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('makeActivationCodeSponsor', [$voucher, $organization]);

        $voucher->makeActivationCode($request->input('client_uid'));

        return SponsorVoucherResource::create($voucher);
    }

    /**
     *
     * @param SendVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @throws AuthorizationException
     * @return SponsorVoucherResource
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

        return SponsorVoucherResource::create($voucher);
    }

    /**
     * @param IndexVouchersExportFieldsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(
        IndexVouchersExportFieldsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('export', [Voucher::class, $organization]);

        return ExportFieldVoucherArrResource::collection(VoucherExport::getExportFields(
            $request->input('type', 'budget')
        ));
    }

    /**
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @throws AuthorizationException|Exception
     * @return VoucherExportArrResource
     */
    public function export(
        IndexVouchersRequest $request,
        Organization $organization
    ): VoucherExportArrResource {
        $this->authorize('show', $organization);
        $this->authorize('export', [Voucher::class, $organization]);

        $fundId = $request->get('fund_id');
        $fields = $request->input('fields', array_pluck(VoucherExport::getExportFields(), 'key'));
        $qrFormat = $request->get('qr_format');
        $dataFormat = $request->get('data_format', 'csv');

        $query = Voucher::searchSponsorQuery($request, $organization, $organization->findFund($fundId));
        $query = VoucherSubQuery::appendFirstUseFields($query);

        $vouchers = $query->with([
            'transactions', 'voucher_relation', 'product', 'fund.fund_config.implementation',
            'token_without_confirmation', 'identity.primary_email', 'identity.record_bsn',
            'product_vouchers', 'top_up_transactions', 'reimbursements_pending',
            'voucher_records.record_type', 'paid_out_transactions', 'fund.organization',
        ])->get();

        $funds = Fund::whereIn('id', $vouchers->pluck('fund_id')->unique()->toArray())->get();

        $exportData = Voucher::exportData($vouchers, $fields, $dataFormat, $qrFormat);

        foreach ($funds as $fund) {
            FundVouchersExportedEvent::dispatch($fund, [
                'fields' => $fields,
                'qr_format' => $qrFormat,
                'data_format' => $dataFormat,
                'voucher_ids' => $vouchers->pluck('id'),
            ]);
        }

        return new VoucherExportArrResource(Arr::only($exportData, ['files', 'data', 'name']));
    }
}
