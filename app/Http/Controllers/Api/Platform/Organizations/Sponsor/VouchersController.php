<?php /** @noinspection PhpUnused */

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\VoucherExport;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\ActivateVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\ActivationCodeVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\AssignVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\DeactivateVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\SendVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreBatchVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreVoucherRequest;
use App\Http\Resources\Sponsor\SponsorVoucherResource;
use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Voucher;
use App\Http\Controllers\Controller;
use App\Services\Forus\Notification\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $identity   = $email ? $request->identity_repo()->getOrMakeByEmail($email) : null;
        $expire_at  = $request->input('expire_at', false);
        $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
        $product_id = $request->input('product_id');

        if ($product_id) {
            $voucher = $fund->makeProductVoucher($identity, $product_id, $expire_at, $note);
        } else {
            $voucher = $fund->makeVoucher($identity, $amount, $expire_at, $note);
        }

        if ($bsn = $request->input('bsn', false)) {
            $voucher->setBsnRelation($bsn)->assignIfExists();
        }

        if (!$voucher->is_granted) {
            if (!$request->input('activate')) {
                $voucher->update([
                    'state' => $voucher::STATE_PENDING,
                ]);
            }

            if ($request->input('activation_code')) {
                $voucher->makeActivationCode($request->input('activation_code_uid'));
            }
        }

        return new SponsorVoucherResource($voucher->updateModel([
            'employee_id' => Employee::getEmployee($request->auth_address())->id
        ]));
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
     * @return SponsorVoucherResource|AnonymousResourceCollection
     * @throws AuthorizationException
     * @noinspection PhpUnused
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
            $identity   = $email ? $request->identity_repo()->getOrMakeByEmail($email) : null;
            $expire_at  = $voucher['expire_at'] ?? false;
            $expire_at  = $expire_at ? Carbon::parse($expire_at) : null;
            $product_id = $voucher['product_id'] ?? false;

            if (!$product_id) {
                $voucherModel = $fund->makeVoucher($identity, $amount, $expire_at, $note);
            } else {
                $voucherModel = $fund->makeProductVoucher($identity, $product_id, $expire_at, $note);
            }

            if ($bsn = ($voucher['bsn'] ?? false)) {
                $voucherModel->setBsnRelation((string) $bsn)->assignIfExists();
            }

            if (!$voucherModel->is_granted) {
                if (!($voucher['activate'] ?? false)) {
                    $voucherModel->update([
                        'state' => $voucherModel::STATE_PENDING,
                    ]);
                }

                if ($voucher['activation_code'] ?? false) {
                    $voucherModel->makeActivationCode($voucher['activation_code_uid'] ?? null);
                }
            }

            return $voucherModel->updateModel([
                'employee_id' => $organization->findEmployee($request->auth_address())->id
            ]);
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
            $voucher->assignToIdentity($request->identity_repo()->getOrMakeByEmail($email));
        } else if ($bsn) {
            $voucher->setBsnRelation($bsn)->assignIfExists();
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

        $request->authorize() ? $voucher->update([
            'state' => $voucher::STATE_ACTIVE,
            'deactivation_reason' => null,
        ]) : null;

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
    public function deactivate(
        DeactivateVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ): SponsorVoucherResource {
        $this->authorize('show', $organization);
        $this->authorize('deactivateSponsor', [$voucher, $organization]);

        $reason = $request->get('deactivation_reason');

        $voucher->update([
            'state' => $voucher::STATE_DEACTIVATED,
            'deactivation_reason' => $reason,
        ]);

        if ($voucher->identity_address && $request->get('notification')) {
            $identityEmail = resolve('forus.services.record')
                ->primaryEmailByAddress($voucher->identity_address);

            resolve(NotificationService::class)->sendMailNotification(
                $identityEmail,
                new DeactivationVoucherMail($voucher, $reason)
            );
        }

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
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return BinaryFileResponse
     * @throws AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportXls(
        IndexVouchersRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        $fund = $organization->findFund($request->get('fund_id'));
        $vouchers = Voucher::searchSponsor($request, $organization, $fund);
        $fileName = date('Y-m-d H:i:s') . '.xls';

        return resolve('excel')->download(new VoucherExport($vouchers), $fileName);
    }

    /**
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return BinaryFileResponse
     * @throws AuthorizationException
     * @noinspection PhpUnused
     */
    public function export(
        IndexVouchersRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        $fund = $organization->findFund($request->get('fund_id'));
        $export_type = $request->get('export_type', 'png');
        $vouchers = Voucher::searchSponsor($request, $organization, $fund);

        if ($vouchers->count() === 0) {
            abort(404, "No vouchers to be exported.");
        }

        if (!$zipFile = Voucher::zipVouchers($vouchers, $export_type)) {
            abort(500, "Couldn't make the archive.");
        }

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    /**
     * @param IndexVouchersRequest $request
     * @param Organization $organization
     * @return array
     * @throws AuthorizationException
     */
    public function exportData(
        IndexVouchersRequest $request,
        Organization $organization
    ): array {
        $this->authorize('show', $organization);
        $this->authorize('viewAnySponsor', [Voucher::class, $organization]);

        $fund = $organization->findFund($request->get('fund_id'));
        $data_only = $request->get('export_only_data', false);
        $export_type = $request->get('export_type', 'png');
        $vouchers = Voucher::searchSponsor($request, $organization, $fund);

        if ($vouchers->count() === 0) {
            abort(404, "No vouchers to be exported.");
        }

        return Voucher::zipVouchersData($vouchers, $export_type, $data_only);
    }
}
