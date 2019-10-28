<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Requests\Api\Platform\Organizations\Vouchers\AssignVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\IndexVouchersRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\SendVoucherRequest;
use App\Http\Requests\Api\Platform\Organizations\Vouchers\StoreVoucherRequest;
use App\Http\Resources\Sponsor\SponsorVoucherResource;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class VouchersController extends Controller
{
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
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexSponsor', [Voucher::class, $organization]);

        return SponsorVoucherResource::collection(
            Voucher::searchSponsor(
                $request,
                $organization,
                Fund::find($request->get('fund_id'))
            )->paginate(
                $request->input('per_page', 25)
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherRequest $request
     * @param Organization $organization
     * @return SponsorVoucherResource|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreVoucherRequest $request,
        Organization $organization
    ) {
        $fund = Fund::find($request->post('fund_id'));

        $this->authorize('show', $organization);
        $this->authorize('storeSponsor', [Voucher::class, $organization, $fund]);

        $identityRepo = resolve('forus.services.identity');
        $recordRepo = resolve('forus.services.record');

        $batch = $request->has('vouchers');
        $vouchers = $batch ? $request->post('vouchers') : [$request->only([
            'expires_at', 'note', 'amount', 'note'
        ])];

        $vouchers = collect($vouchers)->map(function(
            $voucher
        ) use ($fund, $identityRepo,$recordRepo) {
            $note       = $voucher['note'] ?? null;
            $email      = $voucher['email'] ?? false;
            $amount     = $voucher['amount'] ?? 0;
            $identity   = $email ? (
                $recordRepo->identityAddressByEmail($email) ?: $identityRepo->makeByEmail($email)
            ) : null;
            $expires_at = $voucher['expires_at'] ?? false;
            $expires_at = $expires_at ? Carbon::parse($expires_at) : null;

            return $fund->makeVoucher($identity, $amount, $expires_at, $note);
        });

        if ($batch) {
            return SponsorVoucherResource::collection($vouchers);
        }

        return new SponsorVoucherResource($vouchers->first());
    }

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
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucher, $organization]);

        return new SponsorVoucherResource($voucher);
    }

    /**
     *
     * @param AssignVoucherRequest $request
     * @param Organization $organization
     * @param Voucher $voucher
     * @return SponsorVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function assign(
        AssignVoucherRequest $request,
        Organization $organization,
        Voucher $voucher
    ) {
        $this->authorize('show', $organization);
        $this->authorize('assignSponsor', [$voucher, $organization]);

        $email = $request->post('email');
        $identityRepo = resolve('forus.services.identity');
        $recordRepo = resolve('forus.services.record');

        $voucher->assignToIdentity($recordRepo->identityAddressByEmail($email) ?:
            $identityRepo->makeByEmail($email));

        return new SponsorVoucherResource($voucher);
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
    ) {
        $this->authorize('show', $organization);
        $this->authorize('sendByEmailSponsor', [$voucher, $organization]);

        $email = $request->post('email');

        $voucher->sendToEmail($email);

        return new SponsorVoucherResource($voucher);
    }

    /**
     * @param Organization $organization
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportUnassigned(Organization $organization, Request $request) {
        /** @var Collection|Voucher[] $unassigned_vouchers */
        $unassigned_vouchers = Voucher::getUnassignedVouchers(
            $organization,
            $request->get('fromDate'),
            $request->get('toDate')
        );

        if (count($unassigned_vouchers)) {
            if (!file_exists('storage/qr-codes')) {
                mkdir('storage/qr-codes');
            }

            $zip = new \ZipArchive();
            $zip_name = 'storage/qr-codes/qr_codes.zip';
            $csv_name = 'qr_codes.csv';

            $fp = fopen($csv_name, 'w');
            $zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addEmptyDir('images');

            foreach ($unassigned_vouchers as $voucher) {
                foreach ($voucher->tokens as $token) {
                    $full_path = $token->getQrLocalPath();

                    $name = resolve('token_generator')->generate(
                        6, 2
                    );
                    $zip->addFile($full_path, 'images/'.$name);

                    fputcsv($fp, [$name]);
                }
            }

            $zip->addFile($csv_name);
            $zip->close();
            unlink($csv_name);

            return response()->download(public_path($zip_name));
        }
    }
}
