<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReimbursementsSponsorExport extends BaseFieldedExport
{
    protected static string $transKey = 'reimbursements';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id',
        'code',
        'implementation_name',
        'fund_name',
        'amount',
        'employee',
        'email',
        'bsn',
        'iban',
        'iban_name',
        'provider_name',
        'category',
        'title',
        'description',
        'files_count',
        'lead_time',
        'submitted_at',
        'resolved_at',
        'expired',
        'state',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, protected array $fields)
    {
        $this->data = $this->export($request, $organization);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Collection
     */
    protected function export(Request $request, Organization $organization): Collection
    {
        $query = Reimbursement::where('state', '!=', Reimbursement::STATE_DRAFT);
        $query = $query->whereRelation('voucher.fund', 'organization_id', $organization->id);

        $search = new ReimbursementsSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'amount_min', 'amount_max', 'state',
            'expired', 'archived', 'deactivated', 'identity_address', 'implementation_id',
        ]), $query);

        $data = $search->query()->latest()->with([
            'reimbursement_category',
            'voucher.fund.organization',
            'voucher.identity.record_bsn',
            'voucher.identity.primary_email',
            'employee.identity.primary_email',
            'voucher.fund.fund_config.implementation',
        ])->get();

        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (Reimbursement $reimbursement) => array_only(
            $this->getRow($reimbursement),
            $this->fields
        )));
    }

    /**
     * @param Reimbursement $reimbursement
     * @return array
     */
    protected function getRow(Reimbursement $reimbursement): array
    {
        return [
            'id' => $reimbursement->id,
            'code' => '#' . $reimbursement->code,
            'implementation_name' => $reimbursement->voucher->fund->fund_config?->implementation?->name,
            'fund_name' => $reimbursement->voucher->fund->name,
            'amount' => currency_format($reimbursement->amount),
            'employee' => $reimbursement->employee?->identity?->email ?: '-',
            'email' => $reimbursement->voucher->identity->email,
            'bsn' => $reimbursement->voucher->fund->organization->bsn_enabled ?
                ($reimbursement->voucher->identity->record_bsn?->value ?: '-') :
                '-',
            'iban' => $reimbursement->iban,
            'iban_name' => $reimbursement->iban_name,
            'provider_name' => $reimbursement->provider_name ?: '-',
            'category' => $reimbursement->reimbursement_category?->name ?: '-',
            'title' => $reimbursement->title,
            'description' => $reimbursement->description,
            'files_count' => $reimbursement->files_count,
            'lead_time' => $reimbursement->lead_time_locale,
            'submitted_at' => $reimbursement->submitted_at ?
                format_datetime_locale($reimbursement->submitted_at) :
                '-',
            'resolved_at' => $reimbursement->resolved_at ?
                format_datetime_locale($reimbursement->resolved_at) :
                '-',
            'expired' => $reimbursement->expired ? 'Ja' : 'Nee',
            'state' => $reimbursement->state_locale,
        ];
    }
}
