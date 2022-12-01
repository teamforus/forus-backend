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
    public const LOAD = [
        'files.preview',
        'voucher.identity',
        'voucher.fund.organization',
        'employee.organization',
        'employee.roles.translations',
        'employee.roles.permissions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $reimbursement = $this->resource;
        $bsn_enabled = $reimbursement->voucher->fund->organization->bsn_enabled;

        return array_merge($reimbursement->only([
            'id', 'title', 'description', 'amount', 'amount_locale', 'iban', 'iban_name', 'voucher_id',
            'code', 'state', 'state_locale', 'lead_time_locale', 'employee_id', 'expired',
        ]), [
            'resolved' => $reimbursement->isResolved(),
            'identity_email' => $reimbursement->voucher->identity->email,
            'identity_bsn' => $bsn_enabled ? $reimbursement->voucher->identity->bsn : null,
            'employee' => EmployeeResource::create($reimbursement->employee),
            'fund' => $this->fundResource($reimbursement->voucher->fund, $request),
            'files' => FileResource::collection($reimbursement->files),
            'resolved_at' => $reimbursement->resolved_at?->format('Y-m-d'),
            'resolved_at_locale' => $reimbursement->resolved_at_locale,
            'submitted_at' => $reimbursement->submitted_at?->format('Y-m-d'),
            'submitted_at_locale' => $reimbursement->submitted_at_locale,
            'expire_at' => $reimbursement->expire_at?->format('Y-m-d'),
            'expire_at_locale' => $reimbursement->expire_at_locale,
        ], $this->timestamps($reimbursement, 'created_at'));
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
