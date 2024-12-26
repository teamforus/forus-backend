<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VoucherTransactionsSponsorExport extends BaseFieldedExport
{
    protected Collection $data;

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'amount' => 'Bedrag',
        'date_transaction' => 'Datum transactie',
        'date_payment' => 'Datum betaling',
        'date_non_cancelable' => 'Definitieve transactie',
        'bulk_status_locale'  => 'In de wachtrij (dagen)',
        'fund_name' => 'Fonds',
        'product_id' => 'Aanbod ID',
        'product_name' => 'Aanbod naam',
        'provider' => 'Aanbieder',
        'state' => 'Status',
        'amount_extra_cash' => 'Gevraagde bijbetaling',
    ];

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, array $fields)
    {
        $this->data = VoucherTransaction::exportSponsor($request, $organization, $fields);
    }
}
