<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Reimbursement;
use Illuminate\Database\Eloquent\Model;

class ReimbursementsSponsorExport extends BaseExport
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
     * @var array|string[]
     */
    protected array $builderWithArray = [
        'reimbursement_category',
        'voucher.fund.organization',
        'voucher.identity.record_bsn',
        'voucher.identity.primary_email',
        'employee.identity.primary_email',
        'voucher.fund.fund_config.implementation',
    ];

    /**
     * @param Model|Reimbursement $model
     * @return array
     */
    protected function getRow(Model|Reimbursement $model): array
    {
        return [
            'id' => $model->id,
            'code' => '#' . $model->code,
            'implementation_name' => $model->voucher->fund->fund_config?->implementation?->name,
            'fund_name' => $model->voucher->fund->name,
            'amount' => currency_format($model->amount),
            'employee' => $model->employee?->identity?->email ?: '-',
            'email' => $model->voucher->identity->email,
            'bsn' => $model->voucher->fund->organization->bsn_enabled ?
                ($model->voucher->identity->record_bsn?->value ?: '-') :
                '-',
            'iban' => $model->iban,
            'iban_name' => $model->iban_name,
            'provider_name' => $model->provider_name ?: '-',
            'category' => $model->reimbursement_category?->name ?: '-',
            'title' => $model->title,
            'description' => $model->description,
            'files_count' => $model->files_count,
            'lead_time' => $model->lead_time_locale,
            'submitted_at' => $model->submitted_at ?
                format_datetime_locale($model->submitted_at) :
                '-',
            'resolved_at' => $model->resolved_at ?
                format_datetime_locale($model->resolved_at) :
                '-',
            'expired' => $model->expired ? 'Ja' : 'Nee',
            'state' => $model->state_locale,
        ];
    }
}
