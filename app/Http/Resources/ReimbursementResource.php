<?php

namespace App\Http\Resources;

use App\Http\Resources\Tiny\FundTinyResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\Fund;
use App\Models\Reimbursement;
use Illuminate\Http\Request;

/**
 * @property Reimbursement $resource
 */
class ReimbursementResource extends BaseJsonResource
{
    public const array LOAD = [
        'voucher.identity',
        'voucher.fund.organization',
        'voucher.fund.fund_config.implementation',
        'reimbursement_category',
        'voucher_transaction',
    ];

    public const array LOAD_NESTED = [
        'files' => FileResource::class,
        'voucher_transaction' => VoucherTransactionResource::class,
        'voucher.fund' => FundTinyResource::class,
        'voucher.fund.organization' => OrganizationTinyResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reimbursement = $this->resource;

        return array_merge($reimbursement->only([
            'id', 'title', 'description', 'amount', 'amount_locale', 'iban', 'iban_name', 'voucher_id',
            'code', 'state', 'state_locale', 'lead_time_locale', 'employee_id', 'expired',
            'deactivated', 'reason',
        ]), [
            'resolved' => $reimbursement->isResolved(),
            'fund' => $this->fundResource($reimbursement->voucher->fund, $request),
            'files' => FileResource::collection($reimbursement->files),
            'voucher_transaction' => VoucherTransactionResource::create($reimbursement->voucher_transaction),
            ...$this->makeTimestamps($reimbursement->only([
                'resolved_at', 'submitted_at', 'expire_at',
            ]), true),
            ...$this->makeTimestamps($reimbursement->only([
                'created_at',
            ])),
        ]);
    }

    /**
     * @param Fund $fund
     * @param Request $request
     * @return array
     */
    protected function fundResource(Fund $fund, Request $request): array
    {
        return array_merge((new FundTinyResource($fund))->toArray($request), [
            'organization' => (new OrganizationTinyResource($fund->organization)),
        ]);
    }
}
